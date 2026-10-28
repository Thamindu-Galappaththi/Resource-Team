<?php

namespace Tests\Feature;

use App\Enums\CanteenReservationStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\SltEmployeeReservationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SltEmployeeReservationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_resource_hostel_and_canteen_bookings_for_slt_employees(): void
    {
        $this->seed(SltEmployeeReservationSeeder::class);

        $employee = User::query()->where('email', 'slt@nebula.local')->first();
        $colleague = User::query()->where('email', 'slt.colleague@nebula.local')->first();

        $this->assertNotNull($employee);
        $this->assertTrue($employee->hasRole('slt_employee'));
        $this->assertNotNull($colleague);
        $this->assertTrue($colleague->hasRole('slt_employee'));

        $resource = Reservation::query()
            ->where('type', ReservationType::STANDARD->value)
            ->where('title', 'like', 'SLT Demo:%');
        $this->assertGreaterThanOrEqual(15, (clone $resource)->where('requester_id', $employee->id)->count());
        $this->assertGreaterThanOrEqual(2, (clone $resource)->where('requester_id', $colleague->id)->count());
        $this->assertTrue((clone $resource)->where('requester_id', $employee->id)->where('status', ReservationStatus::APPROVED->value)->exists());
        $this->assertTrue((clone $resource)->where('requester_id', $employee->id)->where('status', ReservationStatus::PENDING_APPROVAL->value)->exists());
        $this->assertTrue((clone $resource)->where('requester_id', $employee->id)->where('status', ReservationStatus::CANCELLED->value)->exists());

        $hostel = Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->where('title', 'like', 'SLT Demo:%');
        $this->assertGreaterThanOrEqual(15, (clone $hostel)->where('requester_id', $employee->id)->count());
        $this->assertTrue((clone $hostel)->where('title', 'SLT Demo: Colleague hostel stay')->where('requester_id', $colleague->id)->exists());
        $this->assertTrue((clone $hostel)->where('requester_id', $employee->id)->where('status', ReservationStatus::APPROVED->value)->exists());

        $canteen = CanteenReservation::query()->where('reservation_name', 'like', 'SLT Demo:%');
        $this->assertGreaterThanOrEqual(15, (clone $canteen)->where('requested_by_user_id', $employee->id)->count());
        $this->assertTrue((clone $canteen)->where('reservation_name', 'SLT Demo: Colleague lunch')->where('requested_by_user_id', $colleague->id)->exists());
        $this->assertTrue((clone $canteen)->where('requested_by_user_id', $employee->id)->where('status', CanteenReservationStatus::APPROVED->value)->exists());

        $this->assertGreaterThanOrEqual(5, (clone $resource)->whereDate('reservation_date', '2026-11-19')->count());
        $this->assertTrue((clone $resource)->where('title', 'SLT Demo: Deepavali resource 01')->exists());
        $this->assertTrue((clone $resource)->where('title', 'SLT Demo: Christmas resource 02')->where('requester_id', $colleague->id)->exists());
        $this->assertGreaterThanOrEqual(5, (clone $hostel)->whereHas('hostelStay', fn ($query) => $query->whereDate('check_in_at', '2026-11-19'))->count());
        $this->assertTrue((clone $hostel)->where('title', 'SLT Demo: Deepavali stay 02')->where('requester_id', $colleague->id)->exists());
        $this->assertGreaterThanOrEqual(5, (clone $canteen)->whereDate('reservation_date', '2026-11-19')->count());

        $resourceCount = $resource->count();
        $hostelCount = $hostel->count();
        $canteenCount = $canteen->count();

        $this->seed(SltEmployeeReservationSeeder::class);
        $this->assertSame($resourceCount, Reservation::query()->where('type', ReservationType::STANDARD->value)->where('title', 'like', 'SLT Demo:%')->count());
        $this->assertSame($hostelCount, Reservation::query()->where('type', ReservationType::HOSTEL->value)->where('title', 'like', 'SLT Demo:%')->count());
        $this->assertSame($canteenCount, CanteenReservation::query()->where('reservation_name', 'like', 'SLT Demo:%')->count());
    }
}
