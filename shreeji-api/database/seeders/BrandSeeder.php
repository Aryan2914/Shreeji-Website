<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // Memory / Storage
            ['name' => 'Crucial', 'slug' => 'crucial', 'website' => 'https://www.crucial.com'],
            ['name' => 'Kingston', 'slug' => 'kingston', 'website' => 'https://www.kingston.com'],
            ['name' => 'Samsung', 'slug' => 'samsung', 'website' => 'https://www.samsung.com'],
            ['name' => 'Western Digital', 'slug' => 'western-digital', 'website' => 'https://www.westerndigital.com'],
            ['name' => 'Seagate', 'slug' => 'seagate', 'website' => 'https://www.seagate.com'],
            ['name' => 'Corsair', 'slug' => 'corsair', 'website' => 'https://www.corsair.com'],
            ['name' => 'SK Hynix', 'slug' => 'sk-hynix', 'website' => 'https://www.skhynix.com'],
            ['name' => 'Transcend', 'slug' => 'transcend', 'website' => 'https://www.transcend-info.com'],

            // Connectors / Industrial
            ['name' => 'Phoenix Contact', 'slug' => 'phoenix-contact', 'website' => 'https://www.phoenixcontact.com'],
            ['name' => 'Murrelektronik', 'slug' => 'murrelektronik', 'website' => 'https://www.murrelektronik.com'],
            ['name' => 'Turck', 'slug' => 'turck', 'website' => 'https://www.turck.com'],
            ['name' => 'IFM Electronic', 'slug' => 'ifm-electronic', 'website' => 'https://www.ifm.com'],
            ['name' => 'Lapp', 'slug' => 'lapp', 'website' => 'https://www.lappgroup.com'],
            ['name' => 'Binder', 'slug' => 'binder', 'website' => 'https://www.binder-connector.com'],
            ['name' => 'Amphenol', 'slug' => 'amphenol', 'website' => 'https://www.amphenol.com'],

            // Networking
            ['name' => 'TP-Link', 'slug' => 'tp-link', 'website' => 'https://www.tp-link.com'],
            ['name' => 'D-Link', 'slug' => 'd-link', 'website' => 'https://www.dlink.com'],
            ['name' => 'Cisco', 'slug' => 'cisco', 'website' => 'https://www.cisco.com'],
            ['name' => 'Netgear', 'slug' => 'netgear', 'website' => 'https://www.netgear.com'],
            ['name' => 'Ubiquiti', 'slug' => 'ubiquiti', 'website' => 'https://www.ui.com'],

            // Peripherals
            ['name' => 'Logitech', 'slug' => 'logitech', 'website' => 'https://www.logitech.com'],
            ['name' => 'Dell', 'slug' => 'dell', 'website' => 'https://www.dell.com'],
            ['name' => 'HP', 'slug' => 'hp', 'website' => 'https://www.hp.com'],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'website' => 'https://www.lenovo.com'],

            // Electronics / Power
            ['name' => 'Schneider Electric', 'slug' => 'schneider-electric', 'website' => 'https://www.se.com'],
            ['name' => 'Siemens', 'slug' => 'siemens', 'website' => 'https://www.siemens.com'],
            ['name' => 'ABB', 'slug' => 'abb', 'website' => 'https://www.abb.com'],
            ['name' => 'Omron', 'slug' => 'omron', 'website' => 'https://www.omron.com'],
            ['name' => 'Mean Well', 'slug' => 'mean-well', 'website' => 'https://www.meanwell.com'],
        ];

        foreach ($brands as $brand) {
            Brand::create(array_merge($brand, ['is_active' => true]));
        }
    }
}
