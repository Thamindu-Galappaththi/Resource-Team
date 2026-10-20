<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceRate;
use App\Models\ResourceRateAudit;
use App\Models\ResourcePaymentDiscountRule;
use App\Models\ResourcePaymentDiscountAudit;
use App\Models\ResourceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResourceRateController extends Controller
{
    public function index(): View
    {
        return view('payments.resource-rates', [
            'rates' => ResourceRate::query()->with(['resourceType.category', 'resource'])->latest()->get(),
            'types' => ResourceType::query()->with('category')->orderBy('name')->get(),
            'resources' => Resource::query()->with('type')->orderBy('name_model')->get(),
            'audits' => ResourceRateAudit::query()->with(['actor', 'rate.resourceType'])->latest()->limit(30)->get(),
            'discountRules' => ResourcePaymentDiscountRule::query()->orderBy('customer_category')->get(),
            'discountAudits' => ResourcePaymentDiscountAudit::query()->with(['actor', 'rule'])->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedRate($request);
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
        $this->assertNoOverlap($data, $rate->id);
        $old = $rate->only(['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
        $data['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($request, $rate, $data, $old) {
            $rate->update($data);
            $this->audit($request, $rate, 'updated', $old, $rate->fresh()->only(array_keys($old)));
        });

        return back()->with('success', 'Resource rate updated.');
    }

    public function deactivate(Request $request, ResourceRate $rate): RedirectResponse
    {
        $old = $rate->only(['resource_type_id', 'resource_id', 'base_fee', 'base_duration_hours', 'extra_hour_rate', 'tax_percent', 'effective_from', 'effective_to', 'is_active']);
        DB::transaction(function () use ($request, $rate, $old) {
            $rate->update(['is_active' => false, 'updated_by' => $request->user()->id]);
            $this->audit($request, $rate, 'deactivated', $old, $rate->fresh()->only(array_keys($old)));
        });

        return back()->with('success', 'Resource rate deactivated.');
    }

    public function storeDiscount(Request $request): RedirectResponse
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

        return back()->with('success', 'Customer category discount saved.');
    }

    public function updateDiscount(Request $request, ResourcePaymentDiscountRule $rule): RedirectResponse
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

        return back()->with('success', 'Customer category discount updated.');
    }

    public function deactivateDiscount(Request $request, ResourcePaymentDiscountRule $rule): RedirectResponse
    {
        $old = $rule->only(['customer_category', 'discount_percent', 'is_active']);
        DB::transaction(function () use ($request, $rule, $old) {
            $rule->update(['is_active' => false, 'updated_by' => $request->user()->id]);
            $this->auditDiscount($request, $rule, 'deactivated', $old, $rule->fresh()->only(array_keys($old)));
        });

        return back()->with('success', 'Customer category discount deactivated.');
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

        return $data + ['resource_id' => null, 'effective_to' => null, 'is_active' => true];
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
}
