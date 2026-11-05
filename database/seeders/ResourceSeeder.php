<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $welisara = Location::query()->firstOrCreate(['name' => 'Welisara']);
        $moratuwa = Location::query()->firstOrCreate(['name' => 'Moratuwa']);
        $peradeniya = Location::query()->firstOrCreate(['name' => 'Peradeniya']);

        $space = ResourceCategory::query()->firstOrCreate(['name' => 'Physical Space']);
        $equipment = ResourceCategory::query()->firstOrCreate(['name' => 'Lab Equipment']);
        $av = ResourceCategory::query()->firstOrCreate(['name' => 'Audio Visual']);
        $it = ResourceCategory::query()->firstOrCreate(['name' => 'IT Equipment']);
        $aids = ResourceCategory::query()->firstOrCreate(['name' => 'Teaching Aids']);

        $auditorium = $this->type($space, 'Auditorium', 'Large gathering hall');
        $miniAuditorium = $this->type($space, 'Lecture Hall (small)', 'Smaller auditorium');
        $lectureRoom = $this->type($space, 'Lecture Room', 'Standard teaching room');
        $classroom = $this->type($space, 'Seminar Room', 'Classroom');
        $computerLab = $this->type($space, 'Computer Lab', 'Teaching lab without computers');
        $laboratory = $this->type($space, 'Laboratory', 'Engineering laboratory');
        $workshop = $this->type($space, 'Workshop', 'Practical workshop');
        $sports = $this->type($space, 'Sports Facility', 'Outdoor sports area');
        $setup = $this->type($equipment, 'Practical Setup', 'Bench setup linked to a laboratory');
        $whiteboard = $this->type($aids, 'Whiteboard', 'Whiteboard with marker pens');
        $flipChart = $this->type($aids, 'Flip Chart Board', 'Flip chart boards');
        $sound = $this->type($av, 'Sound System', 'Portable or hall sound system');
        $projector = $this->type($av, 'Multimedia Projector', 'Multimedia projector');
        $desktop = $this->type($it, 'Desktop Computer', 'Desktop computer');
        $laptop = $this->type($it, 'Laptop', 'Laptop computer');
        $wifi = $this->type($it, 'Internet with Wi-Fi', 'Internet with Wi-Fi access');

        $kit = 'Includes a computer, a signal generator, an oscilloscope and a DC power supply';

        $welisaraRooms = [
            ['WEL-AUD-MAIN', $auditorium, 'Main Auditorium'],
            ['WEL-LR-01', $lectureRoom, 'Lecture Room 1'],
            ['WEL-LR-02', $lectureRoom, 'Lecture Room 2'],
            ['WEL-LR-03', $lectureRoom, 'Lecture Room 3'],
            ['WEL-LR-04', $lectureRoom, 'Lecture Room 4'],
            ['WEL-LR-05', $lectureRoom, 'Lecture Room 5'],
            ['WEL-LR-06', $lectureRoom, 'Lecture Room 6'],
            ['WEL-CLAB-01', $computerLab, 'Computer Lab 1 (without computers)'],
            ['WEL-CLAB-02', $computerLab, 'Computer Lab 2 (without computers)'],
            ['WEL-CLAB-03', $computerLab, 'Computer Lab 3 (without computers)'],
            ['WEL-LAB-EEC', $laboratory, 'Electrical, Electronic & Control Lab'],
            ['WEL-LAB-CSP', $laboratory, 'Communication & Signal Processing Lab'],
            ['WEL-LAB-DSM', $laboratory, 'Digital Systems & Microprocessor Lab'],
            ['WEL-LAB-ELN', $laboratory, 'Electronics Lab'],
            ['WEL-LAB-ELC', $laboratory, 'Electrical Lab'],
            ['WEL-PLAY', $sports, 'Playground'],
        ];

        foreach ($welisaraRooms as [$serial, $type, $name]) {
            $this->resource($type, $welisara, $serial, $name);
        }

        $this->linkSetups(
            $this->resource($laboratory, $welisara, 'WEL-LAB-EEC', 'Electrical, Electronic & Control Lab'),
            $this->setups($setup, $welisara, 'WEL-EEC', 'EEC Practical Setup', 4, $kit)
        );
        $this->linkSetups(
            $this->resource($laboratory, $welisara, 'WEL-LAB-CSP', 'Communication & Signal Processing Lab'),
            $this->setups($setup, $welisara, 'WEL-CSP', 'CSP Practical Setup', 12, $kit)
        );
        $this->linkSetups(
            $this->resource($laboratory, $welisara, 'WEL-LAB-DSM', 'Digital Systems & Microprocessor Lab'),
            $this->setups($setup, $welisara, 'WEL-DSM', 'DSM Practical Setup', 12, $kit)
        );
        $this->linkSetups(
            $this->resource($laboratory, $welisara, 'WEL-LAB-ELC', 'Electrical Lab'),
            [$this->resource($setup, $welisara, 'WEL-ELC-MOTOR', 'Electrical motor practical setup with computer interface')]
        );

        foreach ([
            ['MOR-AUD', $auditorium, 'Auditorium'],
            ['MOR-MAUD-01', $miniAuditorium, 'Mini Auditorium 1'],
            ['MOR-MAUD-02', $miniAuditorium, 'Mini Auditorium 2'],
            ['MOR-MAUD-03', $miniAuditorium, 'Mini Auditorium 3'],
            ['MOR-LAB-NGN', $computerLab, 'NGN Lab (without computers)'],
            ['MOR-LAB-HSL', $computerLab, 'HSL Lab (without computers)'],
            ['MOR-LAB-RD', $computerLab, 'R&D Lab (without computers)'],
        ] as [$serial, $type, $name]) {
            $this->resource($type, $moratuwa, $serial, $name);
        }

        foreach ([
            ['PER-AUD', $auditorium, 'Auditorium'],
            ['PER-CLS', $classroom, 'Classroom (non A/C)'],
            ['PER-CLAB-01', $computerLab, 'Computer Lab 1 (without computers)'],
            ['PER-CLAB-02', $computerLab, 'Computer Lab 2 (without computers)'],
            ['PER-CWS', $workshop, 'Cable Workshop (non A/C)'],
        ] as [$serial, $type, $name]) {
            $this->resource($type, $peradeniya, $serial, $name);
        }

        $shared = [
            ['WB', $whiteboard, 'Whiteboard with marker pens'],
            ['FLIP', $flipChart, 'Flip chart boards'],
            ['SND', $sound, 'Sound system'],
            ['PROJ', $projector, 'Multimedia projector'],
            ['PC', $desktop, 'Desktop computer'],
            ['LT', $laptop, 'Laptop'],
            ['WIFI', $wifi, 'Internet with Wi-Fi'],
        ];
        foreach ([['WEL', $welisara], ['MOR', $moratuwa], ['PER', $peradeniya]] as [$prefix, $location]) {
            foreach ($shared as [$code, $type, $name]) {
                $this->resource($type, $location, $prefix.'-'.$code, $name);
            }
        }

        Resource::query()
            ->whereIn('serial_number', [
                'LT-WEL-001', 'LT-WEL-002', 'LT-MOR-001', 'LT-PER-001',
                'PJ-WEL-001', 'PJ-MOR-001', 'PJ-PER-001',
                'LH-WEL-A-001', 'LH-WEL-B-002', 'LH-MOR-A-001',
                'MR-WEL-101', 'MR-MOR-201', 'LAB-WEL-03', 'LAB-PER-01',
            ])
            ->update(['status' => 'inactive']);
    }

    private function type(ResourceCategory $category, string $name, string $description): ResourceType
    {
        return ResourceType::query()->firstOrCreate(
            ['resource_category_id' => $category->id, 'name' => $name],
            ['description' => $description]
        );
    }

    private function resource(ResourceType $type, Location $location, string $serial, string $name): Resource
    {
        return Resource::query()->updateOrCreate(
            ['serial_number' => $serial],
            [
                'resource_type_id' => $type->id,
                'location_id' => $location->id,
                'name_model' => $name,
                'status' => 'active',
                'is_deleted' => false,
                'deleted_at' => null,
            ]
        );
    }

    /**
     * @return list<Resource>
     */
    private function setups(ResourceType $type, Location $location, string $prefix, string $label, int $count, string $note): array
    {
        $rows = [];
        for ($index = 1; $index <= $count; $index++) {
            $rows[] = $this->resource(
                $type,
                $location,
                sprintf('%s-S%02d', $prefix, $index),
                sprintf('%s %02d', $label, $index)
            );
        }

        return $rows;
    }

    /**
     * @param  list<Resource>  $setups
     */
    private function linkSetups(Resource $lab, array $setups): void
    {
        $lab->syncLinks(collect($setups)->pluck('id'));
    }
}
