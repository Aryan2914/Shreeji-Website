<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Memory & RAM',
                'slug' => 'memory-ram',
                'icon' => 'cpu',
                'description' => 'Desktop & Laptop RAM modules — DDR4, DDR5, ECC Server Memory',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Desktop RAM (DDR4)', 'slug' => 'desktop-ram-ddr4', 'sort_order' => 1],
                    ['name' => 'Desktop RAM (DDR5)', 'slug' => 'desktop-ram-ddr5', 'sort_order' => 2],
                    ['name' => 'Laptop RAM (SODIMM DDR4)', 'slug' => 'laptop-ram-sodimm-ddr4', 'sort_order' => 3],
                    ['name' => 'Laptop RAM (SODIMM DDR5)', 'slug' => 'laptop-ram-sodimm-ddr5', 'sort_order' => 4],
                    ['name' => 'Server RAM (ECC)', 'slug' => 'server-ram-ecc', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Storage',
                'slug' => 'storage',
                'icon' => 'hard-drive',
                'description' => 'SSDs, NVMe drives, and Hard Drives for every need',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'SATA SSD (2.5")', 'slug' => 'sata-ssd', 'sort_order' => 1],
                    ['name' => 'NVMe SSD (M.2)', 'slug' => 'nvme-ssd-m2', 'sort_order' => 2],
                    ['name' => 'Internal Hard Drives', 'slug' => 'internal-hard-drives', 'sort_order' => 3],
                    ['name' => 'External Hard Drives', 'slug' => 'external-hard-drives', 'sort_order' => 4],
                    ['name' => 'Portable SSDs', 'slug' => 'portable-ssd', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Connectors & Cables',
                'slug' => 'connectors-cables',
                'icon' => 'cable',
                'description' => 'Industrial connectors, M12, power cables, and data cables',
                'sort_order' => 3,
                'children' => [
                    ['name' => 'M12 Connectors', 'slug' => 'm12-connectors', 'sort_order' => 1],
                    ['name' => 'M8 Connectors', 'slug' => 'm8-connectors', 'sort_order' => 2],
                    ['name' => 'Industrial Cables', 'slug' => 'industrial-cables', 'sort_order' => 3],
                    ['name' => 'Network Cables (Cat5e/Cat6)', 'slug' => 'network-cables', 'sort_order' => 4],
                    ['name' => 'Power Cables & Adapters', 'slug' => 'power-cables', 'sort_order' => 5],
                    ['name' => 'USB Cables & Hubs', 'slug' => 'usb-cables-hubs', 'sort_order' => 6],
                    ['name' => 'HDMI / DisplayPort Cables', 'slug' => 'hdmi-displayport-cables', 'sort_order' => 7],
                ],
            ],
            [
                'name' => 'Networking',
                'slug' => 'networking',
                'icon' => 'network',
                'description' => 'Switches, routers, access points, and network accessories',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Managed Switches', 'slug' => 'managed-switches', 'sort_order' => 1],
                    ['name' => 'Unmanaged Switches', 'slug' => 'unmanaged-switches', 'sort_order' => 2],
                    ['name' => 'Routers', 'slug' => 'routers', 'sort_order' => 3],
                    ['name' => 'Access Points', 'slug' => 'access-points', 'sort_order' => 4],
                    ['name' => 'Network Adapters', 'slug' => 'network-adapters', 'sort_order' => 5],
                    ['name' => 'Patch Panels & Racks', 'slug' => 'patch-panels-racks', 'sort_order' => 6],
                ],
            ],
            [
                'name' => 'Electronics & Industrial',
                'slug' => 'electronics-industrial',
                'icon' => 'circuit-board',
                'description' => 'Industrial automation, sensors, relays, and electronic components',
                'sort_order' => 5,
                'children' => [
                    ['name' => 'Sensors & Transducers', 'slug' => 'sensors-transducers', 'sort_order' => 1],
                    ['name' => 'Relays & Contactors', 'slug' => 'relays-contactors', 'sort_order' => 2],
                    ['name' => 'Power Supplies (SMPS)', 'slug' => 'power-supplies-smps', 'sort_order' => 3],
                    ['name' => 'Terminal Blocks', 'slug' => 'terminal-blocks', 'sort_order' => 4],
                    ['name' => 'Circuit Breakers', 'slug' => 'circuit-breakers', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Peripherals & Accessories',
                'slug' => 'peripherals-accessories',
                'icon' => 'keyboard',
                'description' => 'Keyboards, mice, monitors, and computer accessories',
                'sort_order' => 6,
                'children' => [
                    ['name' => 'Keyboards', 'slug' => 'keyboards', 'sort_order' => 1],
                    ['name' => 'Mice & Trackpads', 'slug' => 'mice-trackpads', 'sort_order' => 2],
                    ['name' => 'Monitors & Displays', 'slug' => 'monitors-displays', 'sort_order' => 3],
                    ['name' => 'Webcams & Headsets', 'slug' => 'webcams-headsets', 'sort_order' => 4],
                    ['name' => 'Laptop Chargers & Adapters', 'slug' => 'laptop-chargers-adapters', 'sort_order' => 5],
                ],
            ],
        ];

        foreach ($categories as $parentData) {
            $children = $parentData['children'] ?? [];
            unset($parentData['children']);

            $parent = Category::create(array_merge($parentData, ['is_active' => true]));

            foreach ($children as $childData) {
                Category::create(array_merge($childData, [
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]));
            }
        }
    }
}
