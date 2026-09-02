<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceLookup
{
    private const LOOKUPS = [
        'locations' => ['locations', 'location'],
        'resource_owners' => ['resource_owners', 'resource_owner'],
        'statuses' => ['statuses', 'status'],
    ];

    public static function table(string $key): ?string
    {
        foreach (self::LOOKUPS[$key] ?? [] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    public static function all(string $key): array
    {
        $table = self::table($key);

        if (! $table) {
            return [];
        }

        $columns = Schema::getColumnListing($table);
        $labelColumn = collect(['name', 'title', 'status', 'location', 'owner_name', 'full_name'])
            ->first(fn ($column) => in_array($column, $columns, true));

        if (! $labelColumn) {
            return [];
        }

        return DB::table($table)
            ->select('id', DB::raw($labelColumn . ' as name'))
            ->orderBy($labelColumn)
            ->get()
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->all();
    }
}
