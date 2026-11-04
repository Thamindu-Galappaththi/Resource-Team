<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ResourcePaymentInvoice;
use App\Models\ResourceType;
use App\Services\ResourceReservationFeeCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResourcePaymentController extends Controller
{
    public function index(): View
    {
        return view('payments.resources');
    }

    public function history(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'resource_type' => ['nullable', 'integer', 'exists:resource_types,id'],
            'status' => ['nullable', 'in:paid,unpaid'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
            'tab' => ['nullable', 'in:payments,dashboard'],
        ]);

        $applyDashboardFilters = function ($query) use ($filters) {
            if (! empty($filters['date_from'])) {
                $query->whereDate('issued_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('issued_at', '<=', $filters['date_to']);
            }
            if (! empty($filters['resource_type'])) {
                $query->whereHas('reservation.items', fn ($items) => $items->whereHas('resource', fn ($resource) => $resource->where('resource_type_id', $filters['resource_type'])));
            }

            return $query;
        };

        $summaryInvoices = $applyDashboardFilters(ResourcePaymentInvoice::query())
            ->get(['status', 'fee_breakdown', 'issued_at']);
        $paidInvoices = $summaryInvoices->where('status', 'paid');
        $unpaidInvoices = $summaryInvoices->where('status', 'unpaid');
        $amountFor = fn ($invoices) => round($invoices->sum(fn ($invoice) => (float) data_get($invoice->fee_breakdown, 'total', 0)), 2);
        $totalAmount = $amountFor($summaryInvoices);
        $paidAmount = $amountFor($paidInvoices);
        $months = collect(range(5, 0))->map(function (int $offset) {
            $month = now()->startOfMonth()->subMonths($offset);

            return ['key' => $month->format('Y-m'), 'label' => $month->format('M'), 'paid' => 0.0, 'unpaid' => 0.0];
        })->keyBy('key');
        $monthlyInvoices = $applyDashboardFilters(ResourcePaymentInvoice::query()
            ->whereDate('issued_at', '>=', now()->startOfMonth()->subMonths(5)->toDateString())
            ->whereDate('issued_at', '<=', now()->endOfMonth()->toDateString()))
            ->get(['status', 'fee_breakdown', 'issued_at']);
        foreach ($monthlyInvoices as $monthlyInvoice) {
            $monthKey = $monthlyInvoice->issued_at?->format('Y-m');
            if ($monthKey && $months->has($monthKey)) {
                $statusKey = $monthlyInvoice->status === 'paid' ? 'paid' : 'unpaid';
                $month = $months->get($monthKey);
                $month[$statusKey] += (float) data_get($monthlyInvoice->fee_breakdown, 'total', 0);
                $months->put($monthKey, $month);
            }
        }
        $months = $months->values()->map(fn ($month) => [...$month, 'total' => round($month['paid'] + $month['unpaid'], 2)]);
        $maxMonthlyAmount = max(1, (float) $months->max('total'));
        $recentPaid = $applyDashboardFilters(ResourcePaymentInvoice::query())
            ->where('status', 'paid')
            ->with(['reservation.requester', 'reservation.createdBy'])
            ->latest('paid_at')
            ->limit(5)
            ->get();

        $paymentsQuery = $applyDashboardFilters(ResourcePaymentInvoice::query())
            ->with(['reservation.requester', 'reservation.createdBy', 'reservation.items.resource.type']);
        if (! empty($filters['status'])) {
            $paymentsQuery->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $paymentsQuery->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhereHas('reservation', function ($reservation) use ($search) {
                        $reservation->where('reference', 'like', '%'.$search.'%')
                            ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('createdBy', fn ($creator) => $creator->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('items', fn ($items) => $items->where('resource_name_snapshot', 'like', '%'.$search.'%')->orWhereHas('resource', fn ($resource) => $resource->where('name_model', 'like', '%'.$search.'%')));
                    });
            });
        }
        $payments = $paymentsQuery->latest('issued_at')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();
        $resourceTypes = ResourceType::query()->orderBy('name')->get(['id', 'name']);

        return view('payments.history', [
            'payments' => $payments,
            'resourceTypes' => $resourceTypes,
            'filters' => $filters,
            'activeTab' => $filters['tab'] ?? 'payments',
            'recentPaid' => $recentPaid,
            'monthlyAmounts' => $months,
            'maxMonthlyAmount' => $maxMonthlyAmount,
            'summary' => [
                'total_count' => $summaryInvoices->count(),
                'paid_count' => $paidInvoices->count(),
                'paid_amount' => $paidAmount,
                'unpaid_count' => $unpaidInvoices->count(),
                'unpaid_amount' => $amountFor($unpaidInvoices),
                'total_amount' => $totalAmount,
                'collection_rate' => $totalAmount > 0 ? round($paidAmount / $totalAmount * 100, 1) : 0.0,
            ],
        ]);
    }

    public function exportHistory(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'resource_type' => ['nullable', 'integer', 'exists:resource_types,id'],
            'status' => ['nullable', 'in:paid,unpaid'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $query = ResourcePaymentInvoice::query()->with(['reservation.requester', 'reservation.createdBy', 'reservation.items.resource']);
        if (! empty($filters['date_from'])) $query->whereDate('issued_at', '>=', $filters['date_from']);
        if (! empty($filters['date_to'])) $query->whereDate('issued_at', '<=', $filters['date_to']);
        if (! empty($filters['resource_type'])) {
            $query->whereHas('reservation.items', fn ($items) => $items->whereHas('resource', fn ($resource) => $resource->where('resource_type_id', $filters['resource_type'])));
        }
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', '%'.$search.'%')->orWhereHas('reservation', fn ($reservation) => $reservation->where('reference', 'like', '%'.$search.'%')->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', '%'.$search.'%'))->orWhereHas('createdBy', fn ($creator) => $creator->where('name', 'like', '%'.$search.'%'))->orWhereHas('items', fn ($items) => $items->where('resource_name_snapshot', 'like', '%'.$search.'%')->orWhereHas('resource', fn ($resource) => $resource->where('name_model', 'like', '%'.$search.'%'))));
            });
        }

        $csvValue = static function ($value): string {
            $value = (string) ($value ?? '');
            return preg_match('/^[=+@\\-\\t\\r]/', $value) ? "'".$value : $value;
        };
        $filename = 'resource-payments-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $csvValue) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Invoice number', 'Reservation reference', 'Requester', 'Resource', 'Amount (LKR)', 'Status', 'Issue date', 'Paid date']);
            $query->latest('issued_at')->chunk(200, function ($invoices) use ($output, $csvValue) {
                foreach ($invoices as $invoice) {
                    $reservation = $invoice->reservation;
                    $resources = $reservation?->items->map(fn ($item) => $item->resource_name_snapshot ?: $item->resource?->name_model)->filter()->unique()->implode(', ') ?: '—';
                    fputcsv($output, array_map($csvValue, [
                        $invoice->invoice_number,
                        $reservation?->reference,
                        $reservation?->requester?->name ?? $reservation?->createdBy?->name,
                        $resources,
                        number_format((float) data_get($invoice->fee_breakdown, 'total', 0), 2, '.', ''),
                        Str::ucfirst($invoice->status),
                        $invoice->issued_at?->format('d M Y'),
                        $invoice->paid_at?->format('d M Y'),
                    ]));
                }
            });
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function lookup(Request $request, ResourceReservationFeeCalculator $fees): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i'],
            'extra_hours' => ['sometimes', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5'],
        ], [
            'reference.regex' => 'Enter a reservation reference in the format RRS-YYYY-000000 or HST-YYYY-000000.',
        ]);
        $reference = strtoupper(trim($data['reference']));
        $reservation = $this->findReservation($reference);

        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found. Check the reference and try again.'], 404);
        }
        if ($message = $this->ineligibleMessage($reservation)) {
            return response()->json(['message' => $message, 'reservation_status' => $reservation->statusEnum()->label()], 409);
        }

        $start = substr((string) $reservation->start_time, 0, 5);
        $end = substr((string) $reservation->end_time, 0, 5);
        $hours = $this->reservedHours($reservation->reservation_date?->toDateString(), $start, $end);
        $invoice = $this->invoiceFor($reservation, $fees);
        $requestedHours = array_key_exists('extra_hours', $data) ? (float) $data['extra_hours'] : (float) $invoice->extra_hours;
        $storedBreakdown = $invoice->fee_breakdown;
        $breakdown = $invoice->status === 'paid'
            ? $storedBreakdown
            : $fees->breakdown($reservation, $requestedHours);
        $payload = [
            'reservation_id' => $reservation->id,
            'reference' => $reservation->reference,
            'title' => $reservation->title ?: $reservation->purpose ?: 'Resource reservation',
            'resource' => $reservation->resourceNames() ?: 'Resource reservation',
            'requester' => $reservation->requester?->name ?? $reservation->createdBy?->name ?? 'Reservation user',
            'bill_to_email' => $reservation->requester?->email ?? $reservation->createdBy?->email,
            'location' => $reservation->location?->name ?? 'Location not available',
            'date' => $reservation->reservation_date?->format('M d, Y'),
            'reservation_status' => $reservation->statusEnum()->label(),
            'issue_date' => $invoice->issued_at?->format('d M Y'),
            'invoice_number' => $invoice->invoice_number,
            'invoice_status' => $invoice->status,
            'start' => $start,
            'end' => $end,
            'hours' => round($hours, 2),
            'payment_status' => $invoice->status,
            'payment_method' => $invoice->payment_method,
            'payment_reference' => $invoice->payment_reference,
            'paid_at' => $invoice->paid_at?->format('d M Y H:i'),
            'paid_by' => $invoice->paidBy?->name,
            'extra_hours' => $invoice->status === 'paid' ? (float) $invoice->extra_hours : $requestedHours,
            'confirmed_extra_hours' => (float) $invoice->extra_hours,
            'can_mark_paid' => $request->user()?->hasPermission('payments.mark-paid') ?? false,
            'is_usage_confirmed' => $invoice->status === 'paid' || $requestedHours <= 0 || round((float) $invoice->extra_hours, 2) === round($requestedHours, 2),
            'currency' => 'LKR',
            'rate_note' => $breakdown['rate_note'],
        ] + $breakdown;

        return response()->json($payload);
    }

    public function confirmUsage(Request $request, ResourceReservationFeeCalculator $fees): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i'],
            'extra_hours' => ['required', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5'],
        ], [
            'reference.regex' => 'Enter a reservation reference in the format RRS-YYYY-000000 or HST-YYYY-000000.',
            'extra_hours.multiple_of' => 'Extra hours must be entered in 0.5 hour increments.',
        ]);
        $reservation = $this->findReservation(strtoupper(trim($data['reference'])));

        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found. Check the reference and try again.'], 404);
        }
        if ($message = $this->ineligibleMessage($reservation)) {
            return response()->json(['message' => $message], 409);
        }

        $breakdown = $fees->breakdown($reservation, (float) $data['extra_hours']);
        if (! $breakdown['has_rate']) {
            return response()->json(['message' => $breakdown['rate_note']], 422);
        }
        $invoice = $this->invoiceFor($reservation, $fees);
        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'This invoice is already paid and cannot be changed.'], 409);
        }
        $paidDuringConfirmation = DB::transaction(function () use ($request, $reservation, $breakdown, $invoice) {
            $lockedInvoice = ResourcePaymentInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($lockedInvoice->status === 'paid') return true;
            $lockedInvoice->update(['extra_hours' => $breakdown['usage_extension_hours'], 'fee_breakdown' => $breakdown]);
            DB::table('resource_payment_usage_audits')->insert([
                'reservation_id' => $reservation->id,
                'actor_id' => $request->user()->id,
                'extra_hours' => $breakdown['usage_extension_hours'],
                'fee_breakdown' => json_encode($breakdown, JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
            return false;
        });
        if ($paidDuringConfirmation) {
            return response()->json(['message' => 'This invoice is already paid and cannot be changed.'], 409);
        }
        $invoice->refresh();

        return response()->json([
            'message' => 'Usage confirmed and recorded.',
            'reference' => $reservation->reference,
            'payment_status' => $invoice->status,
            'invoice_number' => $invoice->invoice_number,
            'extra_hours' => (float) $invoice->extra_hours,
            'confirmed_extra_hours' => (float) $invoice->extra_hours,
            'is_usage_confirmed' => true,
            ...$breakdown,
        ]);
    }

    public function markPaid(Request $request, string $reference, ResourceReservationFeeCalculator $fees): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('payments.mark-paid'), 403);
        validator(['reference' => $reference], ['reference' => ['required', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i']])->validate();
        $data = $request->validate([
            'method' => ['required', 'in:cash,card,bank_transfer'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'extra_hours' => ['required', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5'],
        ]);
        $reservation = $this->findReservation(strtoupper($reference));
        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }
        if ($message = $this->ineligibleMessage($reservation)) {
            return response()->json(['message' => $message], 409);
        }

        return DB::transaction(function () use ($request, $reservation, $data, $fees) {
            $lockedReservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($message = $this->ineligibleMessage($lockedReservation)) {
                return response()->json(['message' => $message], 409);
            }
            $invoice = $this->invoiceFor($lockedReservation, $fees);
            if ($invoice->status === 'paid') {
                return response()->json(['message' => 'This invoice is already paid.'], 409);
            }
            $extraHours = (float) $data['extra_hours'];
            if ($extraHours > 0 && round((float) $invoice->extra_hours, 2) !== round($extraHours, 2)) {
                return response()->json(['message' => 'Confirm the extra usage before marking this invoice as paid.'], 422);
            }
            $breakdown = $fees->breakdown($lockedReservation, $extraHours);
            if (! is_array($breakdown) || empty($breakdown['has_rate'])) {
                return response()->json(['message' => 'An applicable resource rate is required before payment can be recorded.'], 422);
            }
            $invoice->update([
                'extra_hours' => $extraHours,
                'fee_breakdown' => $breakdown,
                'status' => 'paid',
                'paid_at' => now(),
                'paid_by' => $request->user()->id,
                'payment_method' => $data['method'],
                'payment_reference' => filled($data['reference_number'] ?? null) ? trim($data['reference_number']) : null,
            ]);

            return response()->json([
                'message' => 'Payment recorded successfully.',
                'payment_status' => 'paid',
                'invoice_number' => $invoice->invoice_number,
                'paid_at' => $invoice->paid_at?->format('d M Y H:i'),
                'paid_by' => $request->user()->name,
                'payment_method' => $invoice->payment_method,
                'payment_reference' => $invoice->payment_reference,
                'extra_hours' => $extraHours,
                ...$breakdown,
            ]);
        });
    }

    public function print(Request $request, string $reference, ResourceReservationFeeCalculator $fees): View|Response
    {
        validator(['reference' => $reference], [
            'reference' => ['required', 'string', 'regex:/^(RRS|HST)-[0-9]{4}-[0-9]{6}$/i'],
        ])->validate();

        $data = $request->validate(['extra_hours' => ['sometimes', 'numeric', 'min:0', 'max:24', 'multiple_of:0.5']]);
        $reservation = $this->findReservation(strtoupper($reference));
        abort_if(! $reservation, 404, 'Reservation not found.');
        abort_if($this->ineligibleMessage($reservation), 409, $this->ineligibleMessage($reservation));
        $invoice = $this->invoiceFor($reservation, $fees);
        $extraHours = $invoice->status === 'paid'
            ? (float) $invoice->extra_hours
            : (float) ($data['extra_hours'] ?? $invoice->extra_hours);
        $breakdown = $invoice->status === 'paid'
            ? $invoice->fee_breakdown
            : $fees->breakdown($reservation, $extraHours);

        return view('payments.resource-invoice-print', [
            'reservation' => $reservation,
            'invoice' => $invoice,
            'breakdown' => $breakdown,
            'issueDate' => $invoice->issued_at,
            'canMarkPaid' => $request->user()?->hasPermission('payments.mark-paid') ?? false,
        ]);
    }

    private function findReservation(string $reference): ?Reservation
    {
        return Reservation::query()
            ->with(['requester', 'createdBy', 'location', 'items.resource'])
            ->where('reference', $reference)
            ->first();
    }

    private function ineligibleMessage(Reservation $reservation): ?string
    {
        if ($reservation->status === ReservationStatus::CANCELLED->value) {
            return 'This reservation was cancelled and cannot be invoiced.';
        }
        if (in_array($reservation->status, [ReservationStatus::DRAFT->value, ReservationStatus::PENDING_APPROVAL->value, ReservationStatus::CHANGES_REQUESTED->value], true)) {
            return 'This reservation has not been approved yet and cannot be invoiced.';
        }
        if (in_array($reservation->status, [ReservationStatus::REJECTED->value, ReservationStatus::EXPIRED->value], true)) {
            return 'This reservation is not eligible for invoicing.';
        }

        return null;
    }

    private function invoiceFor(Reservation $reservation, ResourceReservationFeeCalculator $fees): ResourcePaymentInvoice
    {
        return DB::transaction(function () use ($reservation, $fees) {
            Reservation::query()->whereKey($reservation->id)->lockForUpdate()->first();
            $invoice = ResourcePaymentInvoice::query()->where('reservation_id', $reservation->id)->lockForUpdate()->first();
            if ($invoice) return $invoice;

            $breakdown = $fees->breakdown($reservation, 0);
            $invoice = ResourcePaymentInvoice::query()->create([
                'reservation_id' => $reservation->id,
                'status' => 'unpaid',
                'extra_hours' => 0,
                'fee_breakdown' => $breakdown,
                'issued_at' => now(),
            ]);
            $invoice->update(['invoice_number' => 'RINV-'.now()->format('Y').'-'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT)]);

            return $invoice->fresh();
        });
    }

    private function reservedHours(?string $date, string $start, string $end): float
    {
        if (! $date) {
            return 0;
        }

        $startsAt = Carbon::parse($date.' '.$start);
        $endsAt = Carbon::parse($date.' '.$end);
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $endsAt->addDay();
        }

        return round($startsAt->diffInMinutes($endsAt) / 60, 2);
    }
}
