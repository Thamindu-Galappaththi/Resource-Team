<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceRate;
use App\Models\ResourceRateAudit;
use App\Models\ResourcePaymentDiscountRule;
use App\Models\ResourcePaymentDiscountAudit;
use App\Models\ResourceType;
use App\Models\Location;
use App\Services\AnnexureRateCardImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResourceRateController extends Controller
{
    public function index(Request $request): View
    {
        $audits = ResourceRateAudit::query()
            ->with(['actor', 'rate.resourceType', 'rate.location'])
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
        $locations = Location::query()->ordered()->get();
        $resources = Resource::query()->with('type')->orderBy('name_model')->get();
        $catalogSearch = trim((string) $request->query('catalog_search', ''));
        $catalogType = trim((string) $request->query('catalog_type', ''));
        $catalogStatus = (string) $request->query('catalog_status', 'active');
        if (! in_array($catalogStatus, ['active', 'inactive', 'all'], true)) {
            $catalogStatus = 'active';
        }
        $catalogQuery = ResourceRate::query()->with(['resourceType.category', 'resource', 'location'])->latest('id');
        if ($catalogSearch !== '') {
            $catalogQuery->where(function ($query) use ($catalogSearch) {
                $query->whereHas('resourceType', fn ($type) => $type->where('name', 'like', '%'.$catalogSearch.'%'))
                    ->orWhere('resource_name', 'like', '%'.$catalogSearch.'%')
                    ->orWhereHas('location', fn ($location) => $location->where('name', 'like', '%'.$catalogSearch.'%'))
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
            'locations' => $locations,
            'resources' => $resources,
            'audits' => $audits,
            'discountRules' => ResourcePaymentDiscountRule::query()->orderBy('customer_category')->get(),
            'discountAudits' => $discountAudits,
            'rateChanges' => $this->mapAudits($audits, $types, $resources, $locations, fn ($audit) => ($audit->rate?->location ? 'Training Centre '.$audit->rate->location->name : 'All locations').' · '.($audit->rate?->resource_name ?: $audit->rate?->resourceType?->name ?? 'Deleted rate')),
            'discountChanges' => $this->mapAudits($discountAudits, $types, $resources, $locations, fn ($audit) => $audit->rule?->customer_category ?? 'Deleted rule'),
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
        $photo = $request->file('photo')?->store('resource-rate-photos', 'public');
        $this->importRateRow($request, $data, $photo);

        return back()->with('success', 'Resource rate added.');
    }

    public function update(Request $request, ResourceRate $rate): RedirectResponse
    {
        $data = $this->validatedRate($request);
        if ($rate->is_active && Carbon::parse($data['effective_from'])->lessThanOrEqualTo($rate->effective_from)) {
            throw ValidationException::withMessages(['effective_from' => 'A new rate version must start after the current version begins.']);
        }
        $photo = $request->file('photo')?->store('resource-rate-photos', 'public') ?? $rate->photo_path;
        $old = $rate->only(['location_id', 'resource_name', 'daily_rate', 'half_day_rate', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active', 'photo_path']);

        DB::transaction(function () use ($request, $rate, $data, $photo, $old) {
            if ($rate->is_active) {
                $newStart = Carbon::parse($data['effective_from'])->startOfDay();
                $previousDay = $newStart->copy()->subDay()->toDateString();
                if (! $rate->effective_to || $rate->effective_to->toDateString() >= $previousDay) {
                    $rate->update(['effective_to' => $previousDay, 'updated_by' => $request->user()->id]);
                    $this->audit($request, $rate, 'version_closed', $old, $rate->fresh()->only(array_keys($old)));
                }
            }
            $this->importRateRow($request, $data, $photo);
        });

        return back()->with('success', 'A new resource rate version was created.');
    }

    public function importCsv(Request $request, AnnexureRateCardImporter $importer): RedirectResponse
    {
        $data = $request->validate(['rate_card' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $count = $importer->importPath($data['rate_card']->getRealPath(), $request->user()->id);
        return back()->with('success', "Imported {$count} Annexure 1 rate rows.");
    }

    public function deactivate(Request $request, ResourceRate $rate): RedirectResponse|JsonResponse
    {
        $old = $rate->only(['resource_type_id', 'resource_id', 'location_id', 'resource_name', 'daily_rate', 'half_day_rate', 'photo_path', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
        DB::transaction(function () use ($request, $rate, $old) {
            $rate->update(['is_active' => false, 'updated_by' => $request->user()->id]);
            $this->audit($request, $rate, 'deactivated', $old, $rate->fresh()->only(array_keys($old)));
        });

        return $this->finished($request, 'Resource rate deactivated.');
    }

    public function activate(Request $request, ResourceRate $rate): RedirectResponse|JsonResponse
    {
        $this->assertNoOverlap([
            'location_id' => $rate->location_id,
            'resource_name' => $rate->resource_name,
            'resource_id' => $rate->resource_id,
            'effective_from' => $rate->effective_from?->toDateString(),
            'effective_to' => $rate->effective_to?->toDateString(),
        ], $rate->id);
        $old = $rate->only(['resource_type_id', 'resource_id', 'location_id', 'resource_name', 'daily_rate', 'half_day_rate', 'photo_path', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
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
            'location_id' => ['required', Rule::in(array_merge(['all'], Location::query()->pluck('id')->map(fn ($id) => (string) $id)->all()))],
            'resource_name' => ['required', 'string', 'max:190'],
            'daily_rate' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'half_day_rate' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'extra_hour_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after:effective_from'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $location = $data['location_id'] === 'all' ? null : Location::query()->findOrFail($data['location_id']);
        return [
            'location' => $location ? 'Training Centre '.$location->name : 'All locations',
            'resource_name' => trim($data['resource_name']),
            'daily_rate' => (float) $data['daily_rate'],
            'half_day_rate' => (float) $data['half_day_rate'],
            'extra_hour_rate' => (float) ($data['extra_hour_rate'] ?? 0),
            'tax_percent' => (float) ($data['tax_percent'] ?? 0),
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
    }

    private function importRateRow(Request $request, array $data, ?string $photo): ResourceRate
    {
        if ($photo) $data['photo'] = $photo;
        return app(AnnexureRateCardImporter::class)->importRow($data, $request->user()->id);
    }

    private function assertNoOverlap(array $data, ?int $exceptId = null): void
    {
        $query = ResourceRate::query()
            ->where('location_id', $data['location_id'] ?? null)
            ->whereRaw('LOWER(resource_name) = ?', [strtolower((string) ($data['resource_name'] ?? ''))])
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

    private function mapAudits($audits, Collection $types, Collection $resources, Collection $locations, callable $itemLabel): Collection
    {
        $tz = config('reservations.display_timezone', 'Asia/Colombo');

        return collect($audits->items())->map(function ($audit) use ($types, $resources, $locations, $itemLabel, $tz) {
            return [
                'at' => $audit->created_at?->copy()->timezone($tz),
                'item' => $itemLabel($audit),
                'action' => ucfirst($audit->action),
                'actor' => $audit->actor?->name ?? 'Unknown',
                'details' => $this->formatAudit($audit->new_values, $types, $resources, $locations),
            ];
        })->values();
    }

    private function formatAudit(?array $values, Collection $types, Collection $resources, Collection $locations): Collection
    {
        if (! is_array($values) || $values === []) {
            return collect();
        }

        $labels = [
            'location_id' => 'Location',
            'resource_name' => 'Resource',
            'resource_type_id' => 'Type',
            'resource_id' => 'Resource',
            'daily_rate' => 'Daily rate',
            'half_day_rate' => 'Half-day rate',
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

        return collect($values)->map(function ($value, $key) use ($labels, $types, $resources, $locations) {
            $label = $labels[$key] ?? str_replace('_', ' ', (string) $key);
            if ($key === 'resource_type_id') {
                $value = $types->firstWhere('id', $value)?->name ?? $value;
            } elseif ($key === 'location_id') {
                $value = $locations->firstWhere('id', $value)?->name ?? ($value ? $value : 'All locations');
            } elseif ($key === 'resource_id') {
                $value = $value ? ($resources->firstWhere('id', $value)?->name_model ?? $value) : 'All resources';
            } elseif ($key === 'location_id') {
                $value = $value ? ($resources->firstWhere('location_id', $value)?->location?->name ?? $value) : 'All locations';
            } elseif (in_array($key, ['base_fee', 'daily_rate', 'half_day_rate', 'extra_hour_rate'], true) && is_numeric($value)) {
                $value = (float) $value === 0.0 ? 'Free' : 'LKR '.number_format((float) $value, 2);
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
