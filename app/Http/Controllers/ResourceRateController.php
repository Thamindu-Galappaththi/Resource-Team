<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceRate;
use App\Models\ResourceRateAudit;
use App\Models\ResourcePaymentDiscountRule;
use App\Models\ResourcePaymentDiscountAudit;
use App\Models\ResourceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResourceRateController extends Controller
{
    public function index(Request $request): View
    {
        $audits = ResourceRateAudit::query()
            ->with(['actor', 'rate.resourceType'])
            ->latest('id')
            ->paginate(10, ['*'], 'rate_page')
            ->withQueryString()
            ->fragment('rr-rate-history');
        $discountAudits = ResourcePaymentDiscountAudit::query()
            ->with(['actor', 'rule'])
            ->latest('id')
            ->paginate(10, ['*'], 'discount_page')
            ->withQueryString()
            ->fragment('rr-discount-history');
        $types = ResourceType::query()->with('category')->orderBy('name')->get();
        $resources = Resource::query()->with('type')->orderBy('name_model')->get();
        $catalogSearch = trim((string) $request->query('catalog_search', ''));
        $catalogType = trim((string) $request->query('catalog_type', ''));
        $catalogStatus = (string) $request->query('catalog_status', 'active');
        if (! in_array($catalogStatus, ['active', 'inactive', 'all'], true)) {
            $catalogStatus = 'active';
        }
        $catalogQuery = ResourceRate::query()->with(['resourceType.category', 'resource'])->latest('id');
        if ($catalogSearch !== '') {
            $catalogQuery->where(function ($query) use ($catalogSearch) {
                $query->whereHas('resourceType', fn ($type) => $type->where('name', 'like', '%'.$catalogSearch.'%'))
                    ->orWhereHas('resource', fn ($resource) => $resource->where('name_model', 'like', '%'.$catalogSearch.'%'));
            });
        }
        if ($catalogType !== '' && ctype_digit($catalogType)) {
            $catalogQuery->where('resource_type_id', (int) $catalogType);
        }
        if ($catalogStatus !== 'all') {
            $catalogQuery->where('is_active', $catalogStatus === 'active');
        }
        $rates = $catalogQuery->paginate(10, ['*'], 'catalog_page')->withQueryString()->fragment('rr-rate-catalog');

        $data = [
            'rates' => $rates,
            'catalogSearch' => $catalogSearch,
            'catalogType' => $catalogType,
            'catalogStatus' => $catalogStatus,
            'rateCount' => ResourceRate::query()->count(),
            'activeRateCount' => ResourceRate::query()->where('is_active', true)->count(),
            'activeDiscountCount' => ResourcePaymentDiscountRule::query()->where('is_active', true)->count(),
            'types' => $types,
            'resources' => $resources,
            'audits' => $audits,
            'discountRules' => ResourcePaymentDiscountRule::query()->orderBy('customer_category')->get(),
            'discountAudits' => $discountAudits,
            'rateChanges' => $this->mapAudits($audits, $types, $resources, fn ($audit) => $audit->rate?->resourceType?->name ?? 'Deleted rate'),
            'discountChanges' => $this->mapAudits($discountAudits, $types, $resources, fn ($audit) => $audit->rule?->customer_category ?? 'Deleted rule'),
        ];

        if ($request->ajax()) {
            return match ($request->query('section')) {
                'rate_history' => view('payments.partials.rate-history', $data),
                'discount_history' => view('payments.partials.discount-history', $data),
                'rate_catalog' => view('payments.partials.rate-catalog', $data),
                default => view('payments.resource-rates', $data),
            };
        }

        return view('payments.resource-rates', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedRate($request);
        $data['is_active'] = true;
        $this->assertNoOverlap($data);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($request, $data) {
            $rate = ResourceRate::query()->create($data);
            $this->audit($request, $rate, 'created', null, $rate->only(['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']));
        });

        return back()->with('success', 'Resource rate added.');
    }

    public function update(Request $request, ResourceRate $rate): RedirectResponse
    {
        $data = $this->validatedRate($request);
        if ($rate->is_active) {
            $this->assertNoOverlap($data, $rate->id);
        }
        $old = $rate->only(['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
        $data['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($request, $rate, $data, $old) {
            $rate->update($data);
            $this->audit($request, $rate, 'updated', $old, $rate->fresh()->only(array_keys($old)));
        });

        return back()->with('success', 'Resource rate updated.');
    }

    public function deactivate(Request $request, ResourceRate $rate): RedirectResponse|JsonResponse
    {
        $old = $rate->only(['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
        DB::transaction(function () use ($request, $rate, $old) {
            $rate->update(['is_active' => false, 'updated_by' => $request->user()->id]);
            $this->audit($request, $rate, 'deactivated', $old, $rate->fresh()->only(array_keys($old)));
        });

        return $this->finished($request, 'Resource rate deactivated.');
    }

    public function activate(Request $request, ResourceRate $rate): RedirectResponse|JsonResponse
    {
        $this->assertNoOverlap([
            'resource_type_id' => $rate->resource_type_id,
            'resource_id' => $rate->resource_id,
            'effective_from' => $rate->effective_from?->toDateString(),
            'effective_to' => $rate->effective_to?->toDateString(),
        ], $rate->id);
        $old = $rate->only(['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
        DB::transaction(function () use ($request, $rate, $old) {
            $rate->update(['is_active' => true, 'updated_by' => $request->user()->id]);
            $this->audit($request, $rate, 'activated', $old, $rate->fresh()->only(array_keys($old)));
        });

        return $this->finished($request, 'Resource rate activated.');
    }

    public function storeDiscount(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge(['customer_category' => strtolower(trim((string) $request->input('customer_category')))]);
        $data = $request->validate([
            'customer_category' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:resource_payment_discount_rules,customer_category'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $data['created_by'] = $data['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($request, $data) {
            $rule = ResourcePaymentDiscountRule::query()->create($data);
            $this->auditDiscount($request, $rule, 'created', null, $rule->only(['customer_category', 'discount_percent', 'is_active']));
        });

        return $this->finished($request, 'Customer category discount saved.', 'discounts');
    }

    public function updateDiscount(Request $request, ResourcePaymentDiscountRule $rule): RedirectResponse|JsonResponse
    {
        $request->merge(['customer_category' => strtolower(trim((string) $request->input('customer_category')))]);
        $data = $request->validate([
            'customer_category' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:resource_payment_discount_rules,customer_category,'.$rule->id],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $data['updated_by'] = $request->user()->id;
        $old = $rule->only(['customer_category', 'discount_percent', 'is_active']);

        DB::transaction(function () use ($request, $rule, $data, $old) {
            $rule->update($data);
            $this->auditDiscount($request, $rule, 'updated', $old, $rule->fresh()->only(array_keys($old)));
        });

        return $this->finished($request, 'Customer category discount updated.', 'discounts');
    }

    public function deactivateDiscount(Request $request, ResourcePaymentDiscountRule $rule): RedirectResponse|JsonResponse
    {
        $old = $rule->only(['customer_category', 'discount_percent', 'is_active']);
        DB::transaction(function () use ($request, $rule, $old) {
            $rule->update(['is_active' => false, 'updated_by' => $request->user()->id]);
            $this->auditDiscount($request, $rule, 'deactivated', $old, $rule->fresh()->only(array_keys($old)));
        });

        return $this->finished($request, 'Customer category discount deactivated.', 'discounts');
    }

    public function activateDiscount(Request $request, ResourcePaymentDiscountRule $rule): RedirectResponse|JsonResponse
    {
        $old = $rule->only(['customer_category', 'discount_percent', 'is_active']);
        DB::transaction(function () use ($request, $rule, $old) {
            $rule->update(['is_active' => true, 'updated_by' => $request->user()->id]);
            $this->auditDiscount($request, $rule, 'activated', $old, $rule->fresh()->only(array_keys($old)));
        });

        return $this->finished($request, 'Customer category discount activated.', 'discounts');
    }

    private function finished(Request $request, string $message, string $tab = 'rates'): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        $fragment = match ($tab) {
            'discounts' => 'rr-discounts',
            'history' => 'rr-history',
            default => 'rr-rates',
        };

        return back()->with('success', $message)->with('rr_tab', $tab)->withFragment($fragment);
    }

    private function validatedRate(Request $request): array
    {
        $data = $request->validate([
            'resource_type_id' => ['required', 'integer', 'exists:resource_types,id'],
            'resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'base_fee' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'base_duration_hours' => ['required', 'numeric', 'gt:0', 'max:9999.99'],
            'extra_hour_rate' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        if (! empty($data['resource_id']) && ! Resource::query()->whereKey($data['resource_id'])->where('resource_type_id', $data['resource_type_id'])->exists()) {
            throw ValidationException::withMessages(['resource_id' => 'Choose a resource belonging to the selected type.']);
        }

        return $data + ['resource_id' => $data['resource_id'] ?? null, 'effective_to' => $data['effective_to'] ?? null];
    }

    private function assertNoOverlap(array $data, ?int $exceptId = null): void
    {
        $query = ResourceRate::query()
            ->where('resource_type_id', $data['resource_type_id'])
            ->where('is_active', true)
            ->where('resource_id', $data['resource_id'] ?? null)
            ->when($exceptId, fn ($rates) => $rates->where('id', '<>', $exceptId))
            ->where(function ($rates) use ($data) {
                $rates->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']);
            });

        if (! empty($data['effective_to'])) {
            $query->whereDate('effective_from', '<=', $data['effective_to']);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['effective_from' => 'The effective dates overlap another active rate for this resource scope.']);
        }
    }

    private function audit(Request $request, ResourceRate $rate, string $action, ?array $old, ?array $new): void
    {
        ResourceRateAudit::query()->create(['resource_rate_id' => $rate->id, 'actor_id' => $request->user()->id, 'action' => $action, 'old_values' => $old, 'new_values' => $new, 'ip_address' => $request->ip()]);
    }

    private function auditDiscount(Request $request, ResourcePaymentDiscountRule $rule, string $action, ?array $old, ?array $new): void
    {
        ResourcePaymentDiscountAudit::query()->create(['discount_rule_id' => $rule->id, 'actor_id' => $request->user()->id, 'action' => $action, 'old_values' => $old, 'new_values' => $new, 'ip_address' => $request->ip()]);
    }

    private function mapAudits($audits, Collection $types, Collection $resources, callable $itemLabel): Collection
    {
        $tz = config('reservations.display_timezone', 'Asia/Colombo');

        return collect($audits->items())->map(function ($audit) use ($types, $resources, $itemLabel, $tz) {
            return [
                'at' => $audit->created_at?->copy()->timezone($tz),
                'item' => $itemLabel($audit),
                'action' => ucfirst($audit->action),
                'actor' => $audit->actor?->name ?? 'Unknown',
                'details' => $this->formatAudit($audit->new_values, $types, $resources),
            ];
        })->values();
    }

    private function formatAudit(?array $values, Collection $types, Collection $resources): Collection
    {
        if (! is_array($values) || $values === []) {
            return collect();
        }

        $labels = [
            'resource_type_id' => 'Type',
            'resource_id' => 'Resource',
            'base_fee' => 'Base fee',
            'base_duration_hours' => 'Included hours',
            'extra_hour_rate' => 'Extra hour rate',
            'tax_percent' => 'Tax',
            'effective_from' => 'Effective from',
            'effective_to' => 'Effective to',
            'is_active' => 'Active',
            'customer_category' => 'Category',
            'discount_percent' => 'Discount',
        ];

        return collect($values)->map(function ($value, $key) use ($labels, $types, $resources) {
            $label = $labels[$key] ?? str_replace('_', ' ', (string) $key);
            if ($key === 'resource_type_id') {
                $value = $types->firstWhere('id', $value)?->name ?? $value;
            } elseif ($key === 'resource_id') {
                $value = $value ? ($resources->firstWhere('id', $value)?->name_model ?? $value) : 'All resources';
            } elseif (in_array($key, ['base_fee', 'extra_hour_rate'], true) && is_numeric($value)) {
                $value = 'LKR '.number_format((float) $value, 2);
            } elseif (in_array($key, ['tax_percent', 'discount_percent'], true) && is_numeric($value)) {
                $value = rtrim(rtrim(number_format((float) $value, 3), '0'), '.').'%';
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif ($value === null || $value === '') {
                $value = '—';
            } elseif (is_string($value) && preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $match)) {
                $value = Carbon::parse($match[1])->format('d M Y');
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }

            return ['label' => $label, 'value' => is_scalar($value) ? (string) $value : json_encode($value)];
        })->values();
    }
}
