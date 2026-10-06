<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SltEmployeeDirectory
{
    /**
     * @return array{
     *     name: string,
     *     nic: string|null,
     *     email: string,
     *     phone: string,
     *     designation: string|null,
     *     service_id: string,
     *     details: array<string, string|null>,
     *     mock: bool
     * }
     */
    public function lookup(string $employeeNo): array
    {
        $employeeNo = trim($employeeNo);

        if ($employeeNo === '') {
            throw new RuntimeException('Enter an employee ID.');
        }

        $record = $this->usesMock()
            ? $this->mockRecord($employeeNo)
            : $this->liveRecord($employeeNo);

        return $this->mapRecord($record, $this->usesMock());
    }

    public function usesMock(): bool
    {
        return (bool) config('services.slt_erp.mock');
    }

    /**
     * @return array<string, mixed>
     */
    private function liveRecord(string $employeeNo): array
    {
        $url = (string) config('services.slt_erp.url');
        $username = (string) config('services.slt_erp.username');
        $password = (string) config('services.slt_erp.password');

        if ($url === '' || $username === '' || $password === '') {
            throw new RuntimeException('SLT employee lookup is not configured on this server.');
        }

        try {
            $response = Http::timeout((int) config('services.slt_erp.timeout', 8))
                ->accept('application/json')
                ->withHeaders([
                    'UserName' => $username,
                    'Password' => $password,
                ])
                ->asJson()
                ->post($url, [
                    'organizationID' => '',
                    'costCenterCode' => '',
                    'employeeNo' => $employeeNo,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('SLT ERP employee lookup failed.', [
                'employee_no' => $employeeNo,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Could not reach the SLT employee directory.');
        }

        if ($response->failed()) {
            throw new RuntimeException('SLT employee directory returned an error.');
        }

        $payload = $response->json();
        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            throw new RuntimeException((string) ($payload['message'] ?? 'No SLT employee was found for that Employee ID.'));
        }

        $row = $payload['data'][0] ?? null;
        if (! is_array($row)) {
            throw new RuntimeException('No SLT employee was found for that Employee ID.');
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function mockRecord(string $employeeNo): array
    {
        $known = $this->sampleDirectory();
        $normalized = $this->normalizeEmployeeNo($employeeNo);

        if (isset($known[$normalized])) {
            return $known[$normalized];
        }

        return [
            'employeeNumber' => $normalized,
            'employeeTitle' => 'MR.',
            'employeeFirstName' => 'Dev',
            'employeeInitials' => 'D',
            'employeeSurname' => 'Employee',
            'designation' => 'Engineer',
            'employeeName' => 'Dev Employee ('.$normalized.')',
            'gradeName' => 'A.3.',
            'officialAddress' => 'High Level Road:::Nugegoda:::LK:',
            'employeeSupervisorNumber' => '000000',
            'email' => strtolower($normalized).'@slt.com.lk',
            'mobileNo' => '+94710000000',
            'dateOfBirth' => '01-JAN-90',
            'gender' => 'Male',
            'orgName' => 'Nebula Institute of Technology',
            'empSection' => 'Development',
            'empDivision' => 'IT',
            'empGroup' => 'Network Group',
            'sectionHead' => '000000',
            'divisionHead' => '000000',
            'groupHead' => '000000',
            'fingerScanLocation' => 'Welisara',
            'employeeCostCode' => '0000',
            'employeeCostCentreName' => 'Development mock',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sampleDirectory(): array
    {
        return [
            '010375' => [
                'employeeNumber' => '010375',
                'employeeTitle' => 'MR.',
                'employeeFirstName' => 'Dhammika',
                'employeeInitials' => 'M G D',
                'employeeSurname' => 'Karunananda',
                'designation' => 'Senior Engineer',
                'employeeName' => 'M G D Karunananda',
                'gradeName' => 'A.3.',
                'officialAddress' => 'High Level Road:::Nugegoda:::LK:',
                'employeeSupervisorNumber' => '007788',
                'email' => 'dkaru@slt.com.lk',
                'mobileNo' => '+94714238497',
                'dateOfBirth' => '24-JUL-70',
                'gender' => 'Male',
                'orgName' => 'Provincial Network_WPSW',
                'empSection' => 'Provincial Operations_Metro 02',
                'empDivision' => 'Regional Operations (Metro)',
                'empGroup' => 'Network Group',
                'sectionHead' => '007788',
                'divisionHead' => '009928',
                'groupHead' => '010104',
                'fingerScanLocation' => 'OPMC Nugegoda',
                'employeeCostCode' => '1174',
                'employeeCostCentreName' => 'Network_WPSW',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{
     *     name: string,
     *     nic: string|null,
     *     email: string,
     *     phone: string,
     *     designation: string|null,
     *     service_id: string,
     *     details: array<string, string|null>,
     *     mock: bool
     * }
     */
    private function mapRecord(array $record, bool $mock): array
    {
        $name = trim((string) ($record['employeeName'] ?? ''));
        if ($name === '') {
            $name = trim(implode(' ', array_filter([
                $record['employeeTitle'] ?? null,
                $record['employeeFirstName'] ?? null,
                $record['employeeSurname'] ?? null,
            ])));
        }

        $email = trim((string) ($record['email'] ?? ''));
        $phone = trim((string) ($record['mobileNo'] ?? ''));

        if ($name === '' || $email === '') {
            throw new RuntimeException('The employee record is missing a name or email address.');
        }

        return [
            'name' => $name,
            'nic' => null,
            'email' => $email,
            'phone' => $phone,
            'designation' => filled($record['designation'] ?? null) ? (string) $record['designation'] : null,
            'service_id' => (string) ($record['employeeNumber'] ?? ''),
            'details' => [
                'title' => $record['employeeTitle'] ?? null,
                'grade' => $record['gradeName'] ?? null,
                'organization' => $record['orgName'] ?? null,
                'section' => $record['empSection'] ?? null,
                'division' => $record['empDivision'] ?? null,
                'group' => $record['empGroup'] ?? null,
                'cost_centre' => $record['employeeCostCentreName'] ?? null,
                'work_location' => $record['fingerScanLocation'] ?? null,
            ],
            'mock' => $mock,
        ];
    }

    private function normalizeEmployeeNo(string $employeeNo): string
    {
        $digits = preg_replace('/\D+/', '', $employeeNo) ?? $employeeNo;

        return strlen($digits) > 0 && strlen($digits) < 6
            ? str_pad($digits, 6, '0', STR_PAD_LEFT)
            : ($digits !== '' ? $digits : $employeeNo);
    }
}
