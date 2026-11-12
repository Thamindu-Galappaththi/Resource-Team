<?php

namespace App\Http\Controllers;

use App\Models\RateCard;
use App\Models\RateRule;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LectureFeeController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->pageData($request);

        if ($request->ajax()) {
            return view('payments.partials.lecture-fee-catalog', $data);
        }

        return view('payments.lecture-fees', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $payload = $this->validatedRate($request);
        $this->ensureUniqueRate($payload);

        RateRule::query()->create($this->ruleAttributes($payload));

        return $this->saved($request, 'Lecture rate added.');
    }

    public function update(Request $request, RateRule $rule): RedirectResponse|JsonResponse
    {
        $payload = $this->validatedRate($request);
        $this->ensureUniqueRate($payload, $rule->id);

        $rule->update($this->ruleAttributes($payload));

        return $this->saved($request, 'Lecture rate updated.');
    }

    public function updateStatus(Request $request, RateRule $rule): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);
        $rule->update(['is_active' => $data['status'] === 'active']);

        return $this->saved($request, $data['status'] === 'active' ? 'Lecture rate activated.' : 'Lecture rate deactivated.');
    }

    public function destroy(Request $request, RateRule $rule): RedirectResponse|JsonResponse
    {
        $rule->delete();

        return $this->saved($request, 'Lecture rate deleted.');
    }

    private function pageData(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'user_type' => ['nullable', Rule::in(array_keys(config('lecture-fees.types')))],
            'department' => ['nullable', Rule::in(array_keys(config('lecture-fees.course_levels')))],
            'qualification' => ['nullable', Rule::in(array_keys(config('lecture-fees.qualifications')))],
            'status' => ['nullable', 'in:active,inactive,all'],
        ]);

        $status = $filters['status'] ?? 'all';
        $query = $this->lectureRules()->latest('id');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $needle = mb_strtolower($search);
            $typeKeys = collect(config('lecture-fees.types'))
                ->filter(fn ($label, $key) => str_contains(mb_strtolower($label), $needle) || str_contains($key, $needle))
                ->keys();
            $courseKeys = collect(config('lecture-fees.course_levels'))
                ->filter(fn ($label, $key) => str_contains(mb_strtolower($label), $needle) || str_contains($key, $needle))
                ->keys();
            $qualificationKeys = collect(config('lecture-fees.qualifications'))
                ->filter(fn ($label, $key) => str_contains(mb_strtolower($label), $needle) || str_contains($key, $needle))
                ->keys();
            $query->where(function ($inner) use ($search, $typeKeys, $courseKeys, $qualificationKeys) {
                $inner->where('user_type', 'like', '%'.$search.'%')
                    ->orWhere('department', 'like', '%'.$search.'%')
                    ->orWhere('qualification', 'like', '%'.$search.'%')
                    ->orWhereIn('user_type', $typeKeys)
                    ->orWhereIn('department', $courseKeys)
                    ->orWhereIn('qualification', $qualificationKeys);
            });
        }
        if (! empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }
        if (! empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (! empty($filters['qualification'])) {
            $query->where('qualification', $filters['qualification']);
        }
        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }

        $rules = $query->paginate(10)->withQueryString();
        $allRules = $this->lectureRules();

        return [
            'rules' => $rules,
            'filters' => array_merge($filters, ['status' => $status]),
            'types' => config('lecture-fees.types'),
            'courseLevels' => config('lecture-fees.course_levels'),
            'qualifications' => config('lecture-fees.qualifications'),
            'officialRates' => config('lecture-fees.rates'),
            'lecturerCount' => $this->humanResources()->count(),
            'totalCount' => (clone $allRules)->count(),
            'activeCount' => (clone $allRules)->where('is_active', true)->count(),
            'inactiveCount' => (clone $allRules)->where('is_active', false)->count(),
        ];
    }

    private function humanResources()
    {
        return Resource::query()
            ->whereHas('type.category', fn ($query) => $query->where('name', 'Human Resource'))
            ->whereNotIn('status', ['deleted', 'decommissioned']);
    }

    private function lectureRules()
    {
        return RateRule::query()->whereHas('rateCard', fn ($query) => $query->where('name', $this->rateCardName()));
    }

    private function validatedRate(Request $request): array
    {
        $types = array_keys(config('lecture-fees.types'));
        $levels = array_keys(config('lecture-fees.course_levels'));
        $qualifications = array_keys(config('lecture-fees.qualifications'));

        $payload = $request->validate([
            'user_type' => ['required', Rule::in($types)],
            'department' => ['required', Rule::in($levels)],
            'qualification' => ['required', Rule::in($qualifications)],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $allowed = config('lecture-fees.rates.'.$payload['user_type'].'.'.$payload['department']) ?? [];
        if ($payload['user_type'] === 'internal_trainer' && ! array_key_exists($payload['qualification'], $allowed)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qualification' => 'Internal trainers use Degree, Masters / CEng, or PHD / DBA rates on the Degree programme.',
            ]);
        }

        return $payload;
    }

    private function ensureUniqueRate(array $payload, ?int $ignoreId = null): void
    {
        $exists = RateRule::query()
            ->where('rate_card_id', $this->lectureRateCard()->id)
            ->where('user_type', $payload['user_type'])
            ->where('department', $payload['department'])
            ->where('qualification', $payload['qualification'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'qualification' => 'An hourly rate already exists for this lecture type, course level, and qualification.',
            ]);
        }
    }

    private function saved(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            $allRules = $this->lectureRules();

            return response()->json([
                'message' => $message,
                'stats' => [
                    'total' => (clone $allRules)->count(),
                    'active' => (clone $allRules)->where('is_active', true)->count(),
                    'inactive' => (clone $allRules)->where('is_active', false)->count(),
                    'lecturers' => $this->humanResources()->count(),
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    private function ruleAttributes(array $payload): array
    {
        return [
            'rate_card_id' => $this->lectureRateCard()->id,
            'user_type' => $payload['user_type'],
            'department' => $payload['department'],
            'qualification' => $payload['qualification'],
            'location' => null,
            'hourly_rate_minor' => (int) round(((float) $payload['hourly_rate']) * 100),
            'is_active' => ($payload['status'] ?? 'active') === 'active',
            'conditions' => [
                'scheme' => 'TDC-LF 01',
                'unit' => 'hour',
            ],
        ];
    }

    private function lectureRateCard(): RateCard
    {
        return RateCard::query()->firstOrCreate(
            ['name' => $this->rateCardName()],
            [
                'currency' => 'LKR',
                'effective_from' => now()->toDateString(),
                'is_active' => true,
            ]
        );
    }

    private function rateCardName(): string
    {
        return 'CHRO Lecture Fee Payment Scheme';
    }
}
