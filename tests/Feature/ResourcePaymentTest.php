<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourcePaymentDiscountAudit;
use App\Models\ResourcePaymentDiscountRule;
use App\Models\ResourceRate;
use App\Models\ResourceRateAudit;
use App\Models\ResourceType;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\ResourceRateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResourcePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_payment_page_shows_locked_usage_and_hides_missing_rate_warning_until_lookup(): void
    {
        $user = User::factory()->role('admin')->create();
        $this->actingAs($user)->get(route('payments.resources'))
            ->assertOk()
            ->assertSee('id="rp-extra-hours" type="number" min="0" max="24" step="0.5"', false)
            ->assertSee('id="rp-rate-warning" class="rp-rate-warning is-hidden"', false)
            ->assertSee('Includes first', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('Reservation history', false)
            ->assertSee('Manage resource rates', false)
            ->assertDontSee('>Lecture fees<', false);
    }

    public function test_header_and_sidebar_actions_follow_payment_permissions(): void
    {
        $management = User::factory()->role('management')->create();
        $this->actingAs($management)->get(route('payments.resources'))
            ->assertOk()
            ->assertSee('Manage resource rates', false)
            ->assertSee('hide-menu">Resource Rates', false)
            ->assertDontSee('Reservation history', false)
            ->assertDontSee('>Lecture fees<', false);
    }

    public function test_seeded_catalog_has_every_requested_resource_type_and_lkr_amount(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $expected = [
            'Main Auditorium' => [40000, 20000],
            'Lecture Room 1' => [25000, 12500],
            'Auditorium' => [20000, 10000],
            'Classroom (non A/C)' => [10000, 5000],
            'Laptop' => [4000, 2000],
        ];

        foreach ($expected as $name => [$daily, $half]) {
            $rate = ResourceRate::query()->where('resource_name', $name)->orWhere('resource_name', str_replace('non A/C', 'Non A/C', $name))->first();
            $this->assertNotNull($rate, $name);
            $this->assertEquals($daily, (float) $rate->daily_rate);
            $this->assertEquals($half, (float) $rate->half_day_rate);
        }
    }

    public function test_lookup_resolves_a_price_for_each_seeded_resource_type(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $user = User::factory()->role('admin')->create();
        $rates = ResourceRate::query()->whereNotNull('resource_name')->where('resource_name', '!=', '')->take(3)->get();

        foreach ($rates as $rate) {
            $resource = Resource::factory()->create([
                'resource_type_id' => $rate->resource_type_id,
                'location_id' => $rate->location_id,
                'name_model' => $rate->resource_name,
            ]);
            $reservation = Reservation::factory()->create([
                'reference' => 'RRS-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
                'reservation_date' => now()->addDay()->toDateString(), 'status' => 'confirmed',
                'start_time' => '09:00:00', 'end_time' => '13:00:00', 'booking_type' => 'half_day_morning',
            ]);
            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id, 'resource_id' => $resource->id,
                'starts_at' => $reservation->reservation_date->copy()->setTime(9, 0),
                'ends_at' => $reservation->reservation_date->copy()->setTime(13, 0),
            ]);

            $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
                ->assertOk()->assertJsonPath('has_rate', true)->assertJsonPath('base_fee', (int) $rate->half_day_rate);
        }
    }

    public function test_specific_resource_rate_takes_precedence_over_type_rate(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500]);
        $resource = $reservation->items->first()->resource;
        $this->makeRate($resource->type, [
            'resource_id' => $resource->id,
            'location_id' => $resource->location_id,
            'resource_name' => $resource->name_model,
            'base_fee' => 7000,
            'base_duration_hours' => 2,
            'extra_hour_rate' => 100,
        ]);

        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
            ->assertOk()->assertJsonPath('base_fee', 7000)->assertJsonPath('has_rate', true);
    }

    public function test_zero_extra_hours_returns_base_fee_with_no_extra_charge(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '11:00:00');

        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
            ->assertOk()->assertJsonPath('base_fee', 5000)->assertJsonPath('billable_extra_hours', 0)
            ->assertJsonPath('extra_hour_fee', 0)->assertJsonPath('total', 5000);
    }

    public function test_decimal_extra_hours_bill_only_actual_usage_above_included_duration(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '11:00:00');

        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 0.5]))
            ->assertOk()->assertJsonPath('extra_hours', 0.5)->assertJsonPath('billable_extra_hours', 0.5)
            ->assertJsonPath('extra_hour_fee', 1250)->assertJsonPath('total', 6250);
    }

    public function test_extra_time_inside_included_base_duration_has_no_surcharge(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '10:00:00');

        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 0.5]))
            ->assertOk()->assertJsonPath('billable_extra_hours', 0.5)->assertJsonPath('extra_hour_fee', 1250)->assertJsonPath('total', 6250);
    }

    public function test_tax_is_applied_after_discount_and_currency_rounds_half_up_to_cents(): void
    {
        config(['payments.resource_rate_discounts.staff' => 10]);
        $user = User::factory()->role('admin')->create();
        $requester = User::factory()->create(['user_type' => 'staff']);
        $reservation = $this->reservationWithRate(['base_fee' => 100.01, 'base_duration_hours' => 1, 'extra_hour_rate' => 10.01, 'tax_percent' => 10], '09:00:00', '10:00:00', $requester);

        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 0.5]))
            ->assertOk()->assertJsonPath('extra_hour_fee', 5.01)->assertJsonPath('subtotal', 105.02)
            ->assertJsonPath('discount', 10.5)->assertJsonPath('tax_fee', 9.45)->assertJsonPath('total', 103.97);
    }

    public function test_100_percent_customer_category_discount_reduces_taxable_total_to_zero(): void
    {
        config(['payments.resource_rate_discounts.student' => 100]);
        $user = User::factory()->role('admin')->create();
        $requester = User::factory()->create(['user_type' => 'student']);
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500, 'tax_percent' => 15], '09:00:00', '11:00:00', $requester);

        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference, 'extra_hours' => 0.5]))
            ->assertOk()->assertJsonPath('discount', 6250)->assertJsonPath('tax_fee', 0)->assertJsonPath('total', 0);
    }

    public function test_missing_and_expired_rates_return_zero_values_and_rate_warning_state(): void
    {
        $user = User::factory()->role('admin')->create();
        $withoutRate = $this->reservationWithRate(null);
        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => $withoutRate->reference]))
            ->assertOk()->assertJsonPath('has_rate', false)->assertJsonPath('total', 0)
            ->assertJsonPath('rate_note', 'Rate details are not available for this resource and reservation date.');

        $expired = $this->reservationWithRate(['base_fee' => 1000, 'base_duration_hours' => 1, 'extra_hour_rate' => 100], '09:00:00', '10:00:00');
        $expiredRate = ResourceRate::query()->where('resource_type_id', $expired->items->first()->resource->resource_type_id)->first();
        $expiredRate->update(['effective_from' => $expired->reservation_date->copy()->subDays(10), 'effective_to' => $expired->reservation_date->copy()->subDay()]);
        $this->getJson(route('payments.resources.lookup', ['reference' => $expired->reference]))
            ->assertOk()->assertJsonPath('has_rate', false)->assertJsonPath('total', 0);
    }

    public function test_lookup_validates_reference_and_reports_not_found(): void
    {
        $user = User::factory()->role('admin')->create();
        $this->actingAs($user)->getJson(route('payments.resources.lookup', ['reference' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('reference');
        $this->getJson(route('payments.resources.lookup', ['reference' => 'RRS-2026-999999']))
            ->assertNotFound()->assertJsonPath('message', 'Reservation not found. Check the reference and try again.');
    }

    public function test_lookup_print_and_usage_work_with_lowercase_reference(): void
    {
        $finance = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '11:00:00');

        $this->actingAs($finance)->getJson(route('payments.resources.lookup', ['reference' => strtolower($reservation->reference)]))
            ->assertOk()->assertJsonPath('has_rate', true)->assertJsonPath('base_fee', 5000);
        $this->get(route('payments.resources.print', ['reference' => strtolower($reservation->reference), 'extra_hours' => 0.5]))
            ->assertOk()->assertSee('LKR 6,250.00');
        $this->postJson(route('payments.resources.usage.confirm'), ['reference' => strtolower($reservation->reference), 'extra_hours' => 0.5])
            ->assertOk()->assertJsonPath('total', 6250);
    }

    public function test_client_totals_are_ignored_and_confirmed_breakdown_is_audited(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '11:00:00');

        $this->actingAs($user)->postJson(route('payments.resources.usage.confirm'), ['reference' => $reservation->reference, 'extra_hours' => 0.5, 'total' => 0.01])
            ->assertOk()->assertJsonPath('extra_hour_fee', 1250)->assertJsonPath('total', 6250)->assertJsonPath('payment_status', 'unpaid');
        $this->assertDatabaseHas('resource_payment_usage_audits', ['reservation_id' => $reservation->id, 'actor_id' => $user->id, 'extra_hours' => 0.5]);
    }

    public function test_usage_cannot_be_confirmed_without_an_applicable_rate(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(null);
        $this->actingAs($user)->postJson(route('payments.resources.usage.confirm'), ['reference' => $reservation->reference, 'extra_hours' => 0])
            ->assertUnprocessable()->assertJsonPath('message', 'Rate details are not available for this resource and reservation date.');
        $this->assertDatabaseCount('resource_payment_usage_audits', 0);
    }

    public function test_admin_can_manage_rates_and_changes_are_audited(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $finance = User::factory()->role('admin')->create();
        $this->actingAs($finance)->get(route('payments.rates.index'))->assertOk()->assertSee('Rate catalog');
        $category = ResourceCategory::query()->firstOrCreate(['name' => 'Finance Test Category']);
        $type = ResourceType::query()->firstOrCreate(['resource_category_id' => $category->id, 'name' => 'Finance Test Type']);
        $locationId = Location::query()->value('id');
        $this->post(route('payments.rates.store'), [
            'location_id' => $locationId,
            'resource_category_id' => $category->id,
            'resource_type_id' => $type->id,
            'daily_rate' => 16000, 'half_day_rate' => 8000,
            'status' => 'active',
        ])->assertSessionHasNoErrors();
        $rate = ResourceRate::query()->where('resource_name', 'Finance Test Type')->latest('id')->firstOrFail();
        $this->assertDatabaseHas('resource_rate_audits', ['resource_rate_id' => $rate->id, 'actor_id' => $finance->id, 'action' => 'imported']);
        $this->put(route('payments.rates.update', $rate), [
            'location_id' => $locationId,
            'resource_category_id' => $category->id,
            'resource_type_id' => $type->id,
            'daily_rate' => 16500, 'half_day_rate' => 8250,
            'status' => 'active',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('resource_rate_audits', ['resource_rate_id' => $rate->id, 'actor_id' => $finance->id, 'action' => 'updated']);
        $this->patch(route('payments.rates.deactivate', $rate))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('resource_rate_audits', ['resource_rate_id' => $rate->id, 'action' => 'deactivated']);
        $this->assertFalse($rate->fresh()->is_active);
        $this->get(route('payments.rates.index', ['catalog_status' => 'all']))
            ->assertOk()
            ->assertSee('title="Activate"', false)
            ->assertSee('ti-circle-check', false)
            ->assertSee('ti-edit', false)
            ->assertDontSee('>Edit</button>', false)
            ->assertDontSee('>Deactivate</button>', false);
        $this->patch(route('payments.rates.activate', $rate))->assertSessionHasNoErrors();
        $this->assertTrue($rate->fresh()->is_active);
        $this->assertDatabaseHas('resource_rate_audits', ['resource_rate_id' => $rate->id, 'action' => 'activated']);
    }

    public function test_admin_can_configure_category_discounts_used_by_invoice_calculation(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $finance = User::factory()->role('admin')->create();
        $requester = User::factory()->create(['user_type' => 'student']);
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '11:00:00', $requester);

        $this->actingAs($finance)->from(route('payments.rates.index'))
            ->post(route('payments.discounts.store'), ['customer_category' => 'student', 'discount_percent' => 20])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('rr_tab', 'discounts')
            ->assertRedirect(route('payments.rates.index').'#rr-discounts');
        $this->actingAs($finance)->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
            ->assertOk()->assertJsonPath('discount_percent', 20)->assertJsonPath('discount', 1000)->assertJsonPath('total', 4000);

        $rule = \App\Models\ResourcePaymentDiscountRule::query()->where('customer_category', 'student')->firstOrFail();
        $this->put(route('payments.discounts.update', $rule), ['customer_category' => 'student', 'discount_percent' => 25])->assertSessionHasNoErrors();
        $this->patch(route('payments.discounts.deactivate', $rule))->assertSessionHasNoErrors();
        $this->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
            ->assertOk()->assertJsonPath('discount_percent', 0)->assertJsonPath('total', 5000);
        $this->assertDatabaseHas('resource_payment_discount_audits', ['discount_rule_id' => $rule->id, 'actor_id' => $finance->id, 'action' => 'created']);
        $this->assertDatabaseHas('resource_payment_discount_audits', ['discount_rule_id' => $rule->id, 'action' => 'updated']);
        $this->assertDatabaseHas('resource_payment_discount_audits', ['discount_rule_id' => $rule->id, 'action' => 'deactivated']);
        $this->get(route('payments.rates.index'))
            ->assertOk()
            ->assertSee('title="Activate"', false)
            ->assertSee('ti-circle-check', false);
        $this->patch(route('payments.discounts.activate', $rule))->assertSessionHasNoErrors();
        $this->assertTrue($rule->fresh()->is_active);
        $this->getJson(route('payments.resources.lookup', ['reference' => $reservation->reference]))
            ->assertOk()->assertJsonPath('discount_percent', 25)->assertJsonPath('total', 3750);
        $this->assertDatabaseHas('resource_payment_discount_audits', ['discount_rule_id' => $rule->id, 'action' => 'activated']);
    }

    public function test_rate_admin_rejects_overlapping_dates_negative_amounts_and_non_payment_users(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $finance = User::factory()->role('admin')->create();
        $category = ResourceCategory::query()->firstOrCreate(['name' => 'Overlap Test Category']);
        $type = ResourceType::query()->firstOrCreate(['resource_category_id' => $category->id, 'name' => 'Overlap Test Type']);
        $overlap = [
            'location_id' => Location::query()->value('id'),
            'resource_category_id' => $category->id,
            'resource_type_id' => $type->id,
            'daily_rate' => 1, 'half_day_rate' => 1, 'status' => 'active',
        ];
        $this->actingAs($finance)->post(route('payments.rates.store'), $overlap)->assertSessionHasNoErrors();
        $this->postJson(route('payments.rates.store'), array_replace($overlap, ['daily_rate' => -1]))->assertUnprocessable()->assertJsonValidationErrors('daily_rate');

        $this->actingAs(User::factory()->role('admin')->create())->get(route('payments.rates.index'))->assertOk()->assertSee('Rate catalog');
        $this->actingAs(User::factory()->role('coordinator')->create())->get(route('payments.rates.index'))->assertForbidden();
    }

    public function test_rate_and_discount_history_paginates(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $finance = User::factory()->role('admin')->create();
        $rate = ResourceRate::query()->firstOrFail();
        $rule = ResourcePaymentDiscountRule::query()->create([
            'customer_category' => 'history',
            'discount_percent' => 5,
            'is_active' => true,
            'created_by' => $finance->id,
            'updated_by' => $finance->id,
        ]);
        foreach (range(1, 11) as $i) {
            ResourceRateAudit::query()->create([
                'resource_rate_id' => $rate->id,
                'actor_id' => $finance->id,
                'action' => 'updated',
                'new_values' => ['base_fee' => 91000 + $i],
            ]);
            ResourcePaymentDiscountAudit::query()->create([
                'discount_rule_id' => $rule->id,
                'actor_id' => $finance->id,
                'action' => 'updated',
                'new_values' => ['discount_percent' => 70 + $i],
            ]);
        }

        $this->actingAs($finance)->get(route('payments.rates.index'))
            ->assertOk()
            ->assertSee('Showing 1–10 of 11')
            ->assertSee('rate_page=2', false)
            ->assertSee('discount_page=2', false)
            ->assertSee('View info')
            ->assertSee('LKR 91,011.00')
            ->assertDontSee('LKR 91,001.00')
            ->assertSee('81%')
            ->assertDontSee('71%');

        $this->get(route('payments.rates.index', ['rate_page' => 2]))
            ->assertOk()
            ->assertSee('LKR 91,001.00')
            ->assertSee('Showing 11–11 of 11');

        $this->get(route('payments.rates.index', ['section' => 'rate_history', 'rate_page' => 2]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->assertSee('LKR 91,001.00')->assertDontSee('Rate catalog');

        $this->get(route('payments.rates.index', ['discount_page' => 2]))
            ->assertOk()
            ->assertSee('71%');
    }

    public function test_rate_catalog_paginates(): void
    {
        $this->seed(ResourceRateSeeder::class);
        $finance = User::factory()->role('admin')->create();
        $type = ResourceType::query()->firstOrFail();
        foreach (range(1, 5) as $i) {
            ResourceRate::query()->create([
                'resource_type_id' => $type->id,
                'resource_id' => null,
                'base_fee' => 100 + $i,
                'base_duration_hours' => 1,
                'extra_hour_rate' => 50,
                'tax_percent' => 0,
                'effective_from' => sprintf('2029-01-%02d', $i),
                'effective_to' => null,
                'is_active' => false,
            ]);
        }

        $total = ResourceRate::query()->count();
        $perPage = 10;
        $lastPage = (int) ceil($total / $perPage);
        $lastFrom = (($lastPage - 1) * $perPage) + 1;
        $firstLabel = "Showing 1–{$perPage} of {$total}";
        $lastLabel = "Showing {$lastFrom}–{$total} of {$total}";

        $this->actingAs($finance)->get(route('payments.rates.index', ['catalog_status' => 'all']))
            ->assertOk()
            ->assertSee('Sample CSV', false)
            ->assertSee('Resource category', false)
            ->assertSee('09:00 – 17:00', false)
            ->assertDontSee('Extra hour rate', false)
            ->assertDontSee('Photo (optional)', false)
            ->assertSee('catalog_page=2', false)
            ->assertSee($firstLabel)
            ->assertSee('id="rr-clear-filters"', false)
            ->assertDontSee('>Edit</button>', false)
            ->assertDontSee('>Deactivate</button>', false)
            ->assertSee('title="Edit"', false)
            ->assertSee('title="Activate"', false);

        $sample = $this->get(route('payments.rates.sample'));
        $sample->assertOk();
        $csv = method_exists($sample, 'streamedContent') ? $sample->streamedContent() : $sample->getContent();
        $this->assertStringContainsString('Resource category', $csv);
        $this->assertStringContainsString('Resource type', $csv);
        $this->assertStringContainsString('Daily rate (LKR)', $csv);

        $this->get(route('payments.rates.index', ['catalog_page' => $lastPage, 'catalog_status' => 'all']))
            ->assertOk()
            ->assertSee($lastLabel);

        $this->get(route('payments.rates.index', ['section' => 'rate_catalog', 'catalog_page' => $lastPage, 'catalog_status' => 'all']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->assertSee($lastLabel)->assertDontSee('Customer category discounts');

        $this->get(route('payments.rates.index', ['catalog_status' => 'inactive']))
            ->assertOk()
            ->assertSee('Showing 1–5 of 5')
            ->assertSee('title="Activate"', false);
    }

    public function test_history_when_uses_sri_lanka_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 03:31:00', 'UTC'));
        try {
            $this->seed(ResourceRateSeeder::class);
            $finance = User::factory()->role('admin')->create();
            $category = ResourceCategory::query()->firstOrCreate(['name' => 'Timezone Test Category']);
            $type = ResourceType::query()->firstOrCreate(['resource_category_id' => $category->id, 'name' => 'Timezone Test Type']);
            $this->actingAs($finance)->post(route('payments.rates.store'), [
                'location_id' => Location::query()->value('id'),
                'resource_category_id' => $category->id,
                'resource_type_id' => $type->id,
                'daily_rate' => 1000,
                'half_day_rate' => 500,
                'status' => 'active',
            ])->assertSessionHasNoErrors();
        } finally {
            Carbon::setTestNow();
        }

        $this->get(route('payments.rates.index'))
            ->assertOk()
            ->assertSee('21 Oct 2026 09:01')
            ->assertDontSee('21 Oct 2026 03:31');
    }

    public function test_print_invoice_uses_server_rate_breakdown(): void
    {
        $user = User::factory()->role('admin')->create();
        $reservation = $this->reservationWithRate(['base_fee' => 5000, 'base_duration_hours' => 2, 'extra_hour_rate' => 2500], '09:00:00', '11:00:00');
        $this->actingAs($user)->get(route('payments.resources.print', ['reference' => $reservation->reference, 'extra_hours' => 0.5]))
            ->assertOk()->assertSee('Half day')->assertSee('LKR 1,250.00')->assertSee('LKR 6,250.00')->assertSee('Unpaid');
    }

    private function reservationWithRate(?array $rate, string $start = '09:00:00', string $end = '11:00:00', ?User $requester = null): Reservation
    {
        $reservation = Reservation::factory()->create([
            'requester_id' => ($requester ?? User::factory()->create())->id,
            'reference' => 'RRS-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'reservation_date' => now()->addDay()->toDateString(), 'status' => 'confirmed', 'start_time' => $start, 'end_time' => $end,
        ]);
        $resource = Resource::factory()->create();
        ReservationItem::factory()->create([
            'reservation_id' => $reservation->id, 'resource_id' => $resource->id,
            'starts_at' => $reservation->reservation_date->copy()->setTimeFromTimeString($start),
            'ends_at' => $reservation->reservation_date->copy()->setTimeFromTimeString($end),
        ]);
        if ($rate) {
            $this->makeRate($resource->type, $rate + [
                'resource_name' => $resource->name_model,
                'location_id' => $resource->location_id,
            ]);
        }

        return $reservation->load('items.resource');
    }

    private function makeRate(ResourceType $type, array $data): ResourceRate
    {
        $base = $data['base_fee'] ?? 5000;

        return ResourceRate::query()->create($data + [
            'resource_type_id' => $type->id, 'resource_id' => null,
            'resource_name' => $type->name, 'location_id' => null,
            'base_fee' => $base, 'daily_rate' => $base, 'half_day_rate' => $base,
            'base_duration_hours' => 2, 'extra_hour_rate' => 2500, 'tax_percent' => 0,
            'effective_from' => now()->subYear()->toDateString(), 'effective_to' => null, 'is_active' => true,
        ]);
    }
}
