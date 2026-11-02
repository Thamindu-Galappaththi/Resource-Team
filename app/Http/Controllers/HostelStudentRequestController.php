<?php

namespace App\Http\Controllers;

use App\Enums\HostelStudentRequestStatus;
use App\Http\Requests\StoreHostelStudentRequest;
use App\Models\HostelRequestLink;
use App\Models\HostelStudentRequest;
use App\Notifications\NewHostelStudentRequest;
use App\Services\NotificationRecipients;
use App\Services\SmsSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HostelStudentRequestController extends Controller
{
    public function __construct(private readonly SmsSender $sms) {}

    public function form(string $token): View
    {
        $this->linkOrFail($token);

        return view('hostel.student-form', [
            'token' => $token,
            'minDate' => now(config('reservations.display_timezone', 'Asia/Colombo'))->toDateString(),
        ]);
    }

    public function submit(StoreHostelStudentRequest $request, string $token): RedirectResponse
    {
        $this->linkOrFail($token);

        $studentRequest = HostelStudentRequest::query()->create($request->safe()->except('website'));

        NotificationRecipients::notify('hostel.manage', new NewHostelStudentRequest($studentRequest));

        return redirect()
            ->route('hostel.student-form.done', $token)
            ->with('student_request_reference', $studentRequest->reference);
    }

    public function done(string $token): View|RedirectResponse
    {
        $this->linkOrFail($token);
        $reference = session('student_request_reference');

        if (! $reference) {
            return redirect()->route('hostel.student-form', $token);
        }

        return view('hostel.student-form-done', compact('token', 'reference'));
    }

    public function index(Request $request): View
    {
        $status = HostelStudentRequestStatus::tryFrom((string) $request->input('status'));

        $requests = HostelStudentRequest::query()
            ->with('decidedBy')
            ->search($request->input('search'))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->latest('created_at')
            ->latest('id')
            ->paginate(10)
            ->appends($request->query());

        return view('hostel.student-requests', [
            'requests' => $requests,
            'link' => HostelRequestLink::current(),
            'statuses' => HostelStudentRequestStatus::cases(),
            'pendingCount' => HostelStudentRequest::query()->where('status', HostelStudentRequestStatus::PENDING->value)->count(),
        ]);
    }

    public function generateLink(Request $request): RedirectResponse
    {
        $existing = HostelRequestLink::current();
        HostelRequestLink::firstOrGenerate($request->user());

        return redirect()
            ->route('hostel.student-requests.index')
            ->with('success', $existing ? 'The student link already exists. Share the same link below.' : 'Student request link created. Share it with students.');
    }

    public function decide(Request $request, HostelStudentRequest $studentRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                HostelStudentRequestStatus::APPROVED->value,
                HostelStudentRequestStatus::REJECTED->value,
                HostelStudentRequestStatus::CANCELLED->value,
            ])],
            'decision_note' => [
                Rule::requiredIf(fn () => $request->input('status') !== HostelStudentRequestStatus::APPROVED->value),
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'decision_note.required' => 'Add a reason so the student knows why.',
        ]);

        $next = HostelStudentRequestStatus::from($data['status']);
        if (! $studentRequest->status->canMoveTo($next)) {
            return back()->withErrors([
                'status' => "{$studentRequest->reference} is already {$studentRequest->status->label()} and cannot be marked {$next->label()}.",
            ]);
        }

        $studentRequest->update([
            'status' => $next,
            'decision_note' => $data['decision_note'] ?? null,
            'decided_by_user_id' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $smsStatus = $this->sms->send($studentRequest->phone, $this->smsMessage($studentRequest));
        $studentRequest->update([
            'sms_status' => $smsStatus,
            'sms_sent_at' => $smsStatus === SmsSender::FAILED ? null : now(),
        ]);

        $message = "{$studentRequest->reference} marked {$next->label()}.";
        $message .= match ($smsStatus) {
            SmsSender::SENT => ' The student was notified by SMS.',
            SmsSender::LOGGED => ' No SMS gateway is configured, so the SMS was written to the log only.',
            default => ' The SMS to the student could not be sent.',
        };

        return back()->with($smsStatus === SmsSender::FAILED ? 'warning' : 'success', $message);
    }

    private function smsMessage(HostelStudentRequest $studentRequest): string
    {
        $text = sprintf(
            'Nebula Hostel: Hi %s, your hostel request %s (%s to %s) has been %s.',
            Str::of($studentRequest->student_name)->explode(' ')->first(),
            $studentRequest->reference,
            $studentRequest->checkInLabel(),
            $studentRequest->checkOutLabel(),
            strtoupper($studentRequest->status->label()),
        );

        if (filled($studentRequest->decision_note)) {
            $text .= ' Note: '.Str::limit($studentRequest->decision_note, 200);
        }

        return $text.' Help: 011 2954 069';
    }

    private function linkOrFail(string $token): HostelRequestLink
    {
        return HostelRequestLink::query()->where('token', $token)->firstOrFail();
    }
}
