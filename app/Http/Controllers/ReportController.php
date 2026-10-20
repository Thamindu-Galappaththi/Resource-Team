<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Location;
use App\Models\ResourceCategory;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const PER_PAGE = 10;

    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): Response
    {
        return $this->htmlPage('reports.index', $this->viewData($request));
    }

    public function feed(Request $request): JsonResponse
    {
        return $this->uncachedJson($this->feedPayload($request));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->filterInput($request);
        $data = $this->reports->getReportData($filters);
        $type = $request->query('export_type', 'reservations');
        if (! in_array($type, ['utilization', 'reservations', 'lecturers'], true)) {
            $type = 'reservations';
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="RRS_%s_report_%s.csv"', $type, now()->format('Ymd_His')),
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($data, $type) {
            $handle = fopen('php://output', 'w');
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
            } elseif ($type === 'lecturers') {
                fputcsv($handle, ['Lecturer', 'Sessions', 'Hours', 'Total (LKR)', 'Paid (LKR)', 'Unpaid (LKR)', 'Status']);
                foreach ($data['financial']['by_lecturer'] as $lec) {
                    fputcsv($handle, [
                        $lec['name'],
                        $lec['sessions'],
                        $lec['total_hours'],
                        number_format($lec['total_lkr'], 2, '.', ''),
                        number_format($lec['paid_lkr'], 2, '.', ''),
                        number_format($lec['unpaid_lkr'], 2, '.', ''),
                        $lec['status'] === 'all_paid' ? 'All Settled' : 'Has Unpaid',
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

    private function viewData(Request $request): array
    {
        $payload = $this->feedPayload($request);
        $filters = $payload['filters'];

        return [
            'payload' => $payload,
            'data' => $payload['data'],
            'locations' => Location::query()->ordered()->get(),
            'categories' => ResourceCategory::query()->orderBy('name')->get(),
            'statuses' => ReservationStatus::cases(),
            'currentFilters' => $filters,
        ];
    }

    private function feedPayload(Request $request): array
    {
        $filters = $this->filterInput($request);
        $data = $this->reports->getReportData($filters);
        $query = array_filter($data['filters'], fn ($value) => $value !== null && $value !== '');

        $utilization = $this->paginateList($data['resource_utilization'], $request, 'util_page', $query);
        $reservations = $this->paginateList($data['reservations'], $request, 'res_page', $query);
        $lecturers = $this->paginateList($data['financial']['by_lecturer'], $request, 'lecturer_page', $query);

        $data['resource_utilization'] = $utilization['items'];
        $data['utilization_pager'] = $utilization['pager'];
        $data['reservation_rows'] = $reservations['items']->map(fn ($res) => [
            'reference' => $res->reference ?? '—',
            'title' => $res->title,
            'purpose' => $res->purpose ?? '—',
            'requester' => $res->requester?->name ?? '—',
            'location' => $res->location?->name ?? '—',
            'date' => $res->reservation_date?->format('d M Y') ?? '—',
            'time' => substr((string) $res->start_time, 0, 5).'–'.substr((string) $res->end_time, 0, 5),
            'attendees' => $res->attendee_count ?? '—',
            'status' => $res->statusEnum()->label(),
            'status_badge' => $res->statusEnum()->badgeClass(),
        ])->values();
        $data['reservation_pager'] = $reservations['pager'];
        $data['financial']['by_lecturer'] = $lecturers['items'];
        $data['financial']['lecturer_pager'] = $lecturers['pager'];
        unset($data['reservations']);

        $export = [
            'utilization' => route('reports.export', array_merge($query, ['export_type' => 'utilization'])),
            'reservations' => route('reports.export', array_merge($query, ['export_type' => 'reservations'])),
            'lecturers' => route('reports.export', array_merge($query, ['export_type' => 'lecturers'])),
        ];

        return [
            'filters' => $data['filters'],
            'data' => $data,
            'html' => view('reports.partials.results', ['data' => $data, 'export' => $export])->render(),
            'charts' => [
                'trend' => $data['booking_trend'],
                'category' => $data['category_breakdown'],
            ],
            'export' => $export,
            'has_filters' => $this->hasUserFilters($filters),
        ];
    }

    private function filterInput(Request $request): array
    {
        return $request->only([
            'from_date',
            'to_date',
            'location_id',
            'resource_category_id',
            'status',
            'search',
        ]);
    }

    private function hasUserFilters(array $filters): bool
    {
        return filled($filters['location_id'] ?? null)
            || filled($filters['resource_category_id'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['search'] ?? null)
            || filled($filters['from_date'] ?? null)
            || filled($filters['to_date'] ?? null);
    }

    private function paginateList(Collection|array $items, Request $request, string $pageKey, array $query): array
    {
        $collection = collect($items)->values();
        $total = $collection->count();
        $last = max(1, (int) ceil(max(1, $total) / self::PER_PAGE));
        $page = max(1, min((int) $request->input($pageKey, 1), $last));
        $slice = $collection->forPage($page, self::PER_PAGE)->values();

        $start = max(1, $page - 2);
        $end = min($last, $start + 4);
        $start = max(1, $end - 4);

        $url = function (int $pageNumber) use ($pageKey, $query): string {
            return route('reports.index', array_merge($query, [$pageKey => $pageNumber]));
        };

        $pages = [];
        for ($item = $start; $item <= $end; $item++) {
            $pages[] = [
                'page' => $item,
                'url' => $url($item),
                'active' => $item === $page,
            ];
        }

        return [
            'items' => $slice,
            'pager' => [
                'from' => $total ? (($page - 1) * self::PER_PAGE) + 1 : 0,
                'to' => min($total, $page * self::PER_PAGE),
                'total' => $total,
                'last' => $last,
                'prev' => $page > 1 ? $url($page - 1) : null,
                'next' => $page < $last ? $url($page + 1) : null,
                'pages' => $pages,
                'page_key' => $pageKey,
            ],
        ];
    }
}
