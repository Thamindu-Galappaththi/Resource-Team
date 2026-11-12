<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceRate;
use App\Models\ResourceRateAudit;
use App\Models\ResourceType;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnexureRateCardImporter
{
    public function importPath(string $path, ?int $actorId = null): int
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'The rate card CSV could not be opened.']);
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
            }
            $header = array_map(fn ($column) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column))), $header);
            $typeCard = in_array('resource type', $header, true) || in_array('resource_type', $header, true);
            $required = $typeCard
                ? ['location', 'resource category', 'resource type', 'daily rate (lkr)', 'half-day rate (lkr)', 'status']
                : ['location', 'resource', 'daily rate (lkr)', 'half-day rate (lkr)', 'effective from', 'status'];
            if ($typeCard && ! in_array('resource category', $header, true) && in_array('resource_category', $header, true)) {
                $required = ['location', 'resource_category', 'resource type', 'daily rate (lkr)', 'half-day rate (lkr)', 'status'];
            }
            if (array_diff($required, $header)) {
                throw ValidationException::withMessages(['file' => $typeCard
                    ? 'CSV headers must include Location, Resource category, Resource type, Daily rate (LKR), Half-day rate (LKR), and Status.'
                    : 'CSV headers must include Location, Resource, Daily rate (LKR), Half-day rate (LKR), Effective from, and Status.']);
            }

            $count = 0;
            DB::transaction(function () use ($handle, $header, $actorId, &$count) {
                while (($values = fgetcsv($handle)) !== false) {
                    if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) continue;
                    if (count($values) !== count($header)) throw ValidationException::withMessages(['file' => 'A CSV row has a different number of columns than the header.']);
                    $row = array_combine($header, $values);
                    $this->importRow($row, $actorId);
                    $count++;
                }
            });

            return $count;
        } finally {
            fclose($handle);
        }
    }

    public function importRow(array $row, ?int $actorId = null): ResourceRate
    {
        $locationLabel = trim((string) ($row['location'] ?? ''));
        $categoryName = trim((string) ($row['resource category'] ?? $row['resource_category'] ?? ''));
        $typeName = trim((string) ($row['resource type'] ?? $row['resource_type'] ?? ''));
        $resourceName = trim((string) ($row['resource'] ?? $row['resource_name'] ?? $typeName));
        $daily = $this->amount($row['daily rate (lkr)'] ?? $row['daily_rate'] ?? null, 'Daily rate');
        $half = $this->amount($row['half-day rate (lkr)'] ?? $row['half_day_rate'] ?? null, 'Half-day rate');
        $extra = $this->amount($row['extra hour rate (lkr)'] ?? $row['extra_hour_rate'] ?? null, 'Extra hour rate', true);
        $tax = $this->amount($row['tax (%)'] ?? $row['tax_percent'] ?? 0, 'Tax', true);
        $from = trim((string) ($row['effective from'] ?? $row['effective_from'] ?? '')) ?: now()->toDateString();
        $to = trim((string) ($row['effective to'] ?? $row['effective_to'] ?? '')) ?: null;
        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if ($status === '') {
            $status = 'active';
        }
        if (! in_array($status, ['active', 'inactive'], true)) {
            throw ValidationException::withMessages(['file' => "Status must be Active or Inactive for {$resourceName}."]);
        }
        $active = $status === 'active';

        if ($resourceName === '' || $locationLabel === '') {
            throw ValidationException::withMessages(['file' => 'Every CSV row needs a location and a resource type.']);
        }
        $fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        if (! $fromDate || $fromDate->format('Y-m-d') !== $from) {
            throw ValidationException::withMessages(['file' => "Effective from must use YYYY-MM-DD for {$resourceName}."]);
        }
        if ($to) {
            $toDate = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
            if (! $toDate || $toDate->format('Y-m-d') !== $to) throw ValidationException::withMessages(['file' => "Effective to must use YYYY-MM-DD for {$resourceName}."]);
        }
        if ($to && $to <= $from) {
            throw ValidationException::withMessages(['file' => "Effective to must be after effective from for {$resourceName}."]);
        }
        if ($tax > 100) {
            throw ValidationException::withMessages(['file' => "Tax cannot exceed 100% for {$resourceName}."]);
        }

        $locationId = $this->locationId($locationLabel);
        $typedImport = $typeName !== '' || ! empty($row['resource_type_id']);
        $resource = $typedImport ? null : $this->findResource($resourceName, $locationId);
        $resourceTypeId = ! empty($row['resource_type_id'])
            ? (int) $row['resource_type_id']
            : ($resource?->resource_type_id ?? $this->resolveType($typedImport ? $typeName : $resourceName, $categoryName ?: null));
        $scopeName = $typedImport ? ($typeName ?: $resourceName) : ($resource?->name_model ?? $resourceName);
        $attributes = [
            'location_id' => $locationId,
            'resource_name' => $scopeName,
            'effective_from' => $from,
        ];
        $rate = ResourceRate::query()
            ->when($resource, fn ($query) => $query->where('resource_id', $resource->id), fn ($query) => $query->where($attributes)->whereNull('resource_id'))
            ->when($resource, fn ($query) => $query->whereDate('effective_from', $from))
            ->first();
        $data = [
            'resource_type_id' => $resourceTypeId,
            'resource_id' => $resource?->id,
            'daily_rate' => $daily,
            'half_day_rate' => $half,
            'base_fee' => $daily,
            'base_duration_hours' => 8,
            'extra_hour_rate' => $extra,
            'tax_percent' => $tax,
            'effective_to' => $to,
            'is_active' => $active,
            'updated_by' => $actorId,
        ];
        if (isset($row['photo']) && trim((string) $row['photo']) !== '') {
            $data['photo_path'] = trim((string) $row['photo']);
        }

        $candidate = [...$data, ...$attributes];
        if ($active) $this->assertNoOverlap($candidate, $rate?->id);
        if ($actorId) $data['created_by'] = $actorId;

        if ($rate) {
            $old = $rate->only(array_keys($data));
            $rate->update($data);
            if ($actorId) ResourceRateAudit::query()->create(['resource_rate_id' => $rate->id, 'actor_id' => $actorId, 'action' => 'imported', 'old_values' => $old, 'new_values' => $rate->fresh()->only(array_keys($data))]);
            return $rate->fresh();
        }

        $rate = ResourceRate::query()->create([...$attributes, ...$data]);
        if ($actorId) ResourceRateAudit::query()->create(['resource_rate_id' => $rate->id, 'actor_id' => $actorId, 'action' => 'imported', 'old_values' => null, 'new_values' => $rate->only(array_keys($data))]);
        return $rate;
    }

    public static function normalizeResourceName(string $name): string
    {
        $name = strtolower(str_replace('&', ' and ', $name));
        $name = preg_replace('/\([^)]*\)/', ' ', $name) ?? $name;
        return preg_replace('/[^a-z0-9]/', '', $name) ?? '';
    }

    private function locationId(string $label): ?int
    {
        if (strtolower($label) === 'all locations') return null;
        $name = preg_replace('/^training\s+centre\s+/i', '', $label) ?? $label;
        $location = Location::query()->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
        if (! $location) throw ValidationException::withMessages(['file' => "Unknown location: {$label}."]);
        return $location->id;
    }

    private function findResource(string $name, ?int $locationId): ?Resource
    {
        $target = self::normalizeResourceName($name);
        return Resource::query()->with('type')->get()->first(function (Resource $resource) use ($target, $locationId) {
            return self::normalizeResourceName($resource->name_model) === $target
                && ($locationId === null || (int) $resource->location_id === $locationId);
        });
    }

    private function resolveType(string $name, ?string $categoryName = null): int
    {
        $target = self::normalizeResourceName($name);
        $categoryTarget = $categoryName ? self::normalizeResourceName($categoryName) : '';
        $type = ResourceType::query()->with('category')->get()->first(function (ResourceType $item) use ($target, $categoryTarget) {
            if (self::normalizeResourceName($item->name) !== $target) {
                return false;
            }
            if ($categoryTarget === '') {
                return true;
            }

            return self::normalizeResourceName((string) $item->category?->name) === $categoryTarget;
        });
        if ($type) {
            return $type->id;
        }
        $category = $categoryName
            ? ResourceCategory::query()->firstOrCreate(['name' => $categoryName])
            : ResourceCategory::query()->firstOrCreate(['name' => 'Annexure Resources']);

        return ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $category->id, 'name' => $name],
            ['description' => 'Imported rate card type']
        )->id;
    }

    private function amount(mixed $value, string $label, bool $blankAllowed = false): float
    {
        $value = trim((string) $value);
        if ($value === '' && $blankAllowed) return 0;
        if (! is_numeric($value) || (float) $value < 0) {
            throw ValidationException::withMessages(['file' => "{$label} must be a non-negative number."]);
        }
        return round((float) $value, 2);
    }

    private function assertNoOverlap(array $data, ?int $exceptId = null): void
    {
        $query = ResourceRate::query()
            ->where('location_id', $data['location_id'])
            ->when(
                ! empty($data['resource_id']),
                fn ($rates) => $rates->where('resource_id', $data['resource_id']),
                fn ($rates) => $rates->whereNull('resource_id'),
            )
            ->where('is_active', true)
            ->where(fn ($rates) => $rates->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']));
        if (! empty($data['effective_to'])) $query->whereDate('effective_from', '<=', $data['effective_to']);
        $normalizedName = self::normalizeResourceName($data['resource_name']);
        $overlaps = $query->get()->first(fn (ResourceRate $rate) => $rate->id !== $exceptId
            && (
                (! empty($data['resource_id']) && (int) $rate->resource_id === (int) $data['resource_id'])
                || self::normalizeResourceName($rate->resource_name) === $normalizedName
            ));
        if ($overlaps) {
            throw ValidationException::withMessages(['effective_from' => "Active effective dates overlap for {$data['resource_name']} at this location."]);
        }
    }
}
