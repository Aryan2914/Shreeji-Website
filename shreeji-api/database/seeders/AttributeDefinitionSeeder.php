<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Category;
use Illuminate\Database\Seeder;

class AttributeDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        // ── RAM Attributes ─────────────────────────────
        $ramCategories = Category::where('slug', 'like', '%ram%')->pluck('id');

        $ramAttributes = [
            ['key' => 'capacity', 'label' => 'Memory Capacity', 'type' => 'select', 'options' => ['4GB', '8GB', '16GB', '32GB', '64GB'], 'unit' => 'GB', 'is_required' => true, 'sort_order' => 1],
            ['key' => 'type', 'label' => 'Memory Type', 'type' => 'select', 'options' => ['DDR3', 'DDR4', 'DDR5'], 'is_required' => true, 'sort_order' => 2],
            ['key' => 'speed', 'label' => 'Clock Speed', 'type' => 'select', 'options' => ['2133', '2400', '2666', '3200', '4800', '5200', '5600', '6000'], 'unit' => 'MHz', 'is_required' => true, 'sort_order' => 3],
            ['key' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['UDIMM', 'SODIMM', 'RDIMM'], 'is_required' => true, 'sort_order' => 4],
            ['key' => 'voltage', 'label' => 'Operating Voltage', 'type' => 'number', 'unit' => 'V', 'is_required' => false, 'sort_order' => 5],
            ['key' => 'cas_latency', 'label' => 'CAS Latency', 'type' => 'text', 'is_required' => false, 'sort_order' => 6],
            ['key' => 'ecc', 'label' => 'ECC Support', 'type' => 'boolean', 'is_required' => false, 'sort_order' => 7],
            ['key' => 'heat_spreader', 'label' => 'Heat Spreader', 'type' => 'boolean', 'is_required' => false, 'sort_order' => 8],
        ];

        foreach ($ramCategories as $catId) {
            foreach ($ramAttributes as $attr) {
                $data = $attr;
                if (isset($data['options'])) {
                    $data['options'] = json_encode($data['options']);
                }
                AttributeDefinition::create(array_merge($data, ['category_id' => $catId]));
            }
        }

        // ── SSD/Storage Attributes ─────────────────────
        $storageCategories = Category::whereIn('slug', ['sata-ssd', 'nvme-ssd-m2', 'portable-ssd'])->pluck('id');

        $ssdAttributes = [
            ['key' => 'capacity', 'label' => 'Storage Capacity', 'type' => 'select', 'options' => ['128GB', '256GB', '512GB', '1TB', '2TB', '4TB'], 'is_required' => true, 'sort_order' => 1],
            ['key' => 'interface', 'label' => 'Interface', 'type' => 'select', 'options' => ['SATA III 6Gb/s', 'PCIe Gen3 NVMe', 'PCIe Gen4 NVMe', 'PCIe Gen5 NVMe', 'USB 3.2'], 'is_required' => true, 'sort_order' => 2],
            ['key' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['2.5"', 'M.2 2280', 'M.2 2242', 'mSATA', 'Portable'], 'is_required' => true, 'sort_order' => 3],
            ['key' => 'read_speed', 'label' => 'Sequential Read Speed', 'type' => 'number', 'unit' => 'MB/s', 'is_required' => false, 'sort_order' => 4],
            ['key' => 'write_speed', 'label' => 'Sequential Write Speed', 'type' => 'number', 'unit' => 'MB/s', 'is_required' => false, 'sort_order' => 5],
            ['key' => 'tbw', 'label' => 'Endurance (TBW)', 'type' => 'number', 'unit' => 'TB', 'is_required' => false, 'sort_order' => 6],
            ['key' => 'nand_type', 'label' => 'NAND Type', 'type' => 'select', 'options' => ['TLC', 'QLC', 'MLC', 'SLC'], 'is_required' => false, 'sort_order' => 7],
            ['key' => 'dram_cache', 'label' => 'DRAM Cache', 'type' => 'boolean', 'is_required' => false, 'sort_order' => 8],
        ];

        foreach ($storageCategories as $catId) {
            foreach ($ssdAttributes as $attr) {
                $data = $attr;
                if (isset($data['options'])) {
                    $data['options'] = json_encode($data['options']);
                }
                AttributeDefinition::create(array_merge($data, ['category_id' => $catId]));
            }
        }

        // ── HDD Attributes ─────────────────────────────
        $hddCategories = Category::whereIn('slug', ['internal-hard-drives', 'external-hard-drives'])->pluck('id');

        $hddAttributes = [
            ['key' => 'capacity', 'label' => 'Storage Capacity', 'type' => 'select', 'options' => ['500GB', '1TB', '2TB', '4TB', '8TB', '12TB'], 'is_required' => true, 'sort_order' => 1],
            ['key' => 'rpm', 'label' => 'RPM', 'type' => 'select', 'options' => ['5400', '7200', '10000'], 'is_required' => false, 'sort_order' => 2],
            ['key' => 'form_factor', 'label' => 'Form Factor', 'type' => 'select', 'options' => ['3.5"', '2.5"'], 'is_required' => true, 'sort_order' => 3],
            ['key' => 'interface', 'label' => 'Interface', 'type' => 'select', 'options' => ['SATA III', 'USB 3.0', 'USB-C'], 'is_required' => true, 'sort_order' => 4],
            ['key' => 'cache', 'label' => 'Cache Size', 'type' => 'number', 'unit' => 'MB', 'is_required' => false, 'sort_order' => 5],
        ];

        foreach ($hddCategories as $catId) {
            foreach ($hddAttributes as $attr) {
                $data = $attr;
                if (isset($data['options'])) {
                    $data['options'] = json_encode($data['options']);
                }
                AttributeDefinition::create(array_merge($data, ['category_id' => $catId]));
            }
        }

        // ── M12 Connector Attributes ───────────────────
        $m12Category = Category::where('slug', 'm12-connectors')->first();

        if ($m12Category) {
            $m12Attributes = [
                ['key' => 'coding', 'label' => 'Coding', 'type' => 'select', 'options' => ['A-coded', 'B-coded', 'C-coded', 'D-coded', 'X-coded', 'T-coded'], 'is_required' => true, 'sort_order' => 1],
                ['key' => 'pins', 'label' => 'Number of Pins', 'type' => 'select', 'options' => ['3', '4', '5', '8', '12', '17'], 'is_required' => true, 'sort_order' => 2],
                ['key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male', 'Female'], 'is_required' => true, 'sort_order' => 3],
                ['key' => 'ip_rating', 'label' => 'IP Rating', 'type' => 'select', 'options' => ['IP65', 'IP67', 'IP68', 'IP69K'], 'is_required' => true, 'sort_order' => 4],
                ['key' => 'cable_length', 'label' => 'Cable Length', 'type' => 'number', 'unit' => 'm', 'is_required' => false, 'sort_order' => 5],
                ['key' => 'connector_type', 'label' => 'Connector Type', 'type' => 'select', 'options' => ['Straight', 'Angled (90°)', 'Panel Mount', 'Bulkhead'], 'is_required' => false, 'sort_order' => 6],
                ['key' => 'voltage_rating', 'label' => 'Voltage Rating', 'type' => 'number', 'unit' => 'V', 'is_required' => false, 'sort_order' => 7],
                ['key' => 'current_rating', 'label' => 'Current Rating', 'type' => 'number', 'unit' => 'A', 'is_required' => false, 'sort_order' => 8],
                ['key' => 'shielded', 'label' => 'Shielded', 'type' => 'boolean', 'is_required' => false, 'sort_order' => 9],
                ['key' => 'material', 'label' => 'Housing Material', 'type' => 'select', 'options' => ['Nickel-plated brass', 'Stainless steel', 'Plastic (PA)'], 'is_required' => false, 'sort_order' => 10],
            ];

            foreach ($m12Attributes as $attr) {
                $data = $attr;
                if (isset($data['options'])) {
                    $data['options'] = json_encode($data['options']);
                }
                AttributeDefinition::create(array_merge($data, ['category_id' => $m12Category->id]));
            }
        }

        // ── Network Switch Attributes ──────────────────
        $switchCategories = Category::whereIn('slug', ['managed-switches', 'unmanaged-switches'])->pluck('id');

        $switchAttributes = [
            ['key' => 'ports', 'label' => 'Number of Ports', 'type' => 'select', 'options' => ['5', '8', '16', '24', '48'], 'is_required' => true, 'sort_order' => 1],
            ['key' => 'speed', 'label' => 'Port Speed', 'type' => 'select', 'options' => ['100Mbps', '1Gbps', '2.5Gbps', '10Gbps'], 'is_required' => true, 'sort_order' => 2],
            ['key' => 'poe', 'label' => 'PoE Support', 'type' => 'select', 'options' => ['None', 'PoE', 'PoE+', 'PoE++'], 'is_required' => false, 'sort_order' => 3],
            ['key' => 'sfp_ports', 'label' => 'SFP/SFP+ Uplink Ports', 'type' => 'number', 'is_required' => false, 'sort_order' => 4],
            ['key' => 'managed', 'label' => 'Managed', 'type' => 'boolean', 'is_required' => true, 'sort_order' => 5],
            ['key' => 'rack_mountable', 'label' => 'Rack Mountable', 'type' => 'boolean', 'is_required' => false, 'sort_order' => 6],
        ];

        foreach ($switchCategories as $catId) {
            foreach ($switchAttributes as $attr) {
                $data = $attr;
                if (isset($data['options'])) {
                    $data['options'] = json_encode($data['options']);
                }
                AttributeDefinition::create(array_merge($data, ['category_id' => $catId]));
            }
        }
    }
}
