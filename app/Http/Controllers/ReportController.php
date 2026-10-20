<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Location;
use App\Models\ResourceCategory;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'from_date',
            'to_date',
            'location_id',
            'resource_category_id',
            'status',
            'search',
        ]);

        $data = $this->reports->getReportData($filters);

        return view('reports.index', [
            'data' => $data,
            'locations' => Location::query()->ordered()->get(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'statuses' => ReservationStatus::cases(),
            'currentFilters' => $data['filters'],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'from_date',
            'to_date',
            'location_id',
            'resource_category_id',
            'status',
            'search',
        ]);

        $data = $this->reports->getReportData($filters);
        $type = $request->query('export_type', 'reservations');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="RRS_%s_report_%s.csv"', $type, now()->format('Ymd_His')),
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($data, $type) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'utilization') {
                fputcsv($handle, ['Resource ID', 'Resource Name / Model', 'Serial No', 'Category', 'Type', 'Location', 'Status', 'Bookings Count', 'Booked Hours', 'Utilization %', 'Decision Recommendation']);
                foreach ($data['resource_utilization'] as $res) {
                    fputcsv($handle, [
                        $res['id'],
                        $res['name'],
                        $res['serial_number'] ?? 'N/A',
                        $res['category'],
                        $res['type'],
                        $res['location'],
                        ucfirst(str_replace('_', ' ', $res['status'])),
                        $res['total_bookings'],
                        $res['booked_hours'],
                        $res['utilization_pct'].'%',
                        $res['decision_recommendation'],
                    ]);
                }
            } else {
                fputcsv($handle, ['Reference', 'Title', 'Purpose', 'Requester', 'Location', 'Date', 'Start Time', 'End Time', 'Attendees', 'Status']);
                foreach ($data['reservations'] as $res) {
                    fputcsv($handle, [
                        $res->reference ?? '—',
                        $res->title,
                        $res->purpose ?? '—',
                        $res->requester?->name ?? '—',
                        $res->location?->name ?? '—',
                        $res->reservation_date?->format('Y-m-d') ?? '—',
                        $res->start_time ?? '—',
                        $res->end_time ?? '—',
                        $res->attendee_count ?? '0',
                        $res->statusEnum()->label(),
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
