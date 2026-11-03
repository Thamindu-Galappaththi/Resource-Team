<?php

namespace Database\Seeders;

use App\Enums\ReservationType;
use App\Models\HostelStayDetail;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceRate;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Hostel beds are one `resources` row per location (category "Hostel Room", type "Bed")
 * whose bed_counts hold every bed group (Permanent 10, Ladies 40, Male 40 by default).
 * Safe to re-run: older per-room records are merged into the location's hostel record,
 * their bookings move with them, and the old rows are soft deleted so history stays intact.
 */
class HostelRoomSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $category = ResourceCategory::query()->firstOrCreate(
                ['name' => config('hostel.category_name', 'Hostel Room')]
            );
            $bed = ResourceType::query()->firstOrCreate(
                ['resource_category_id' => $category->id, 'name' => config('hostel.bed_type_name', 'Bed')],
                ['description' => 'Hostel beds']
            );

            $this->convertLegacyTypes($category, $bed);

            foreach (config('resources.locations', []) as $name) {
                $location = Location::query()->firstOrCreate(['name' => $name]);
                $hostel = $this->hostelFor($bed, $location);
                $this->mergeOtherRooms($category, $location, $hostel);
            }

            $this->tidyBookings();
        });
    }

    private function convertLegacyTypes(ResourceCategory $category, ResourceType $bed): void
    {
        ResourceType::query()
            ->where('resource_category_id', $category->id)
            ->whereKeyNot($bed->id)
            ->get()
            ->each(function (ResourceType $type) use ($bed) {
                Resource::withoutGlobalScope('not_deleted')->where('resource_type_id', $type->id)->update(['resource_type_id' => $bed->id]);
                ResourceRate::query()->where('resource_type_id', $type->id)->update(['resource_type_id' => $bed->id]);
                $type->delete();
            });
    }

    private function hostelFor(ResourceType $bed, Location $location): Resource
    {
        $defaults = collect(config('hostel.bed_groups', []))->map(fn (array $group) => (int) $group['default'])->all();
        $hostel = Resource::query()
            ->where('resource_type_id', $bed->id)
            ->where('location_id', $location->id)
            ->whereNotIn('status', ['deleted', 'decommissioned'])
            ->orderByRaw('CASE WHEN name_model = ? THEN 0 ELSE 1 END', [$location->name.' Hostel'])
            ->orderBy('id')
            ->first();

        $hostel ??= new Resource([
            'resource_type_id' => $bed->id,
            'location_id' => $location->id,
            'serial_number' => 'HOSTEL-'.strtoupper(substr($location->name, 0, 3)),
        ]);

        $counts = is_array($hostel->bed_counts) && $hostel->bed_counts !== [] ? $hostel->bed_counts : $defaults;
        $hostel->fill([
            'name_model' => $location->name.' Hostel',
            'status' => 'active',
            'bed_counts' => $counts,
            'bed_count' => array_sum($counts),
        ])->save();

        return $hostel;
    }

    private function mergeOtherRooms(ResourceCategory $category, Location $location, Resource $hostel): void
    {
        DB::table('reservation_items')
            ->where('resource_id', $hostel->id)
            ->where('resource_name_snapshot', '!=', $hostel->name_model)
            ->update(['resource_name_snapshot' => $hostel->name_model, 'updated_at' => now()]);

        DB::table('resource_links')
            ->where('resource_id', $hostel->id)
            ->orWhere('linked_resource_id', $hostel->id)
            ->delete();

        $others = Resource::query()
            ->where('location_id', $location->id)
            ->whereKeyNot($hostel->id)
            ->whereHas('type', fn ($query) => $query->where('resource_category_id', $category->id))
            ->pluck('id');

        if ($others->isEmpty()) {
            return;
        }

        DB::table('reservation_items')
            ->whereIn('resource_id', $others)
            ->update(['resource_id' => $hostel->id, 'resource_name_snapshot' => $hostel->name_model, 'updated_at' => now()]);

        Resource::query()->whereKey($others)->update([
            'status' => 'deleted',
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }

    /** One item per hostel booking, holding one bed per guest, and every stay has a bed group. */
    private function tidyBookings(): void
    {
        HostelStayDetail::query()->whereNull('bed_group')->update(['bed_group' => 'male']);

        Reservation::query()
            ->where('type', ReservationType::HOSTEL->value)
            ->with(['items', 'hostelStay'])
            ->get()
            ->each(function (Reservation $reservation) {
                $items = $reservation->items->sortBy('id')->values();
                $first = $items->first();
                if (! $first) {
                    return;
                }
                $guests = (int) ($reservation->hostelStay?->number_of_guests ?: $items->sum('quantity') ?: 1);
                $items->slice(1)->each->delete();
                if ((int) round((float) $first->quantity) !== $guests) {
                    $first->update(['quantity' => $guests]);
                }
            });
    }
}
