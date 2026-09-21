<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductSku;
use App\Models\PriceTier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRamProducts();
        $this->seedSsdProducts();
        $this->seedM12Products();
        $this->seedNetworkingProducts();
    }

    private function seedRamProducts(): void
    {
        $crucial = Brand::where('slug', 'crucial')->first();
        $kingston = Brand::where('slug', 'kingston')->first();
        $corsair = Brand::where('slug', 'corsair')->first();
        $desktopDdr4 = Category::where('slug', 'desktop-ram-ddr4')->first();
        $laptopDdr4 = Category::where('slug', 'laptop-ram-sodimm-ddr4')->first();

        // ── Crucial 8GB DDR4 Desktop ───────────────────
        $product = Product::create([
            'category_id' => $desktopDdr4->id,
            'brand_id' => $crucial->id,
            'name' => 'Crucial 8GB DDR4 3200MHz UDIMM Desktop RAM',
            'short_description' => 'Genuine Crucial 8GB DDR4-3200 CL22 desktop memory module. Plug and play upgrade.',
            'description' => '<p>High-performance 8GB DDR4-3200 UDIMM designed for desktops. Features auto-overclocking to 3200MHz, CL22 latency, and 1.2V operation for maximum efficiency.</p><ul><li>100% tested for reliability</li><li>Backed by Crucial limited lifetime warranty</li><li>Compatible with Intel and AMD platforms</li></ul>',
            'hsn_code' => '84733020',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => 'Crucial Limited Lifetime Warranty',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '8', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'type', 'attribute_value' => 'DDR4'],
            ['attribute_key' => 'speed', 'attribute_value' => '3200', 'attribute_unit' => 'MHz'],
            ['attribute_key' => 'form_factor', 'attribute_value' => 'UDIMM'],
            ['attribute_key' => 'voltage', 'attribute_value' => '1.2', 'attribute_unit' => 'V'],
            ['attribute_key' => 'cas_latency', 'attribute_value' => 'CL22'],
        ]);

        $this->addSku($product, 'CRU-8GB-DDR4-3200-UD', 1600, 2200, 1950, 25, 'Rack-A1-Shelf-1');

        // ── Crucial 16GB DDR4 Desktop ──────────────────
        $product = Product::create([
            'category_id' => $desktopDdr4->id,
            'brand_id' => $crucial->id,
            'name' => 'Crucial 16GB DDR4 3200MHz UDIMM Desktop RAM',
            'short_description' => 'Genuine Crucial 16GB DDR4-3200 CL22 desktop memory for heavy multitasking and content creation.',
            'description' => '<p>Premium 16GB DDR4-3200 UDIMM for desktops demanding more memory. Ideal for multitasking, photo editing, and gaming.</p>',
            'hsn_code' => '84733020',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => 'Crucial Limited Lifetime Warranty',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '16', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'type', 'attribute_value' => 'DDR4'],
            ['attribute_key' => 'speed', 'attribute_value' => '3200', 'attribute_unit' => 'MHz'],
            ['attribute_key' => 'form_factor', 'attribute_value' => 'UDIMM'],
            ['attribute_key' => 'voltage', 'attribute_value' => '1.2', 'attribute_unit' => 'V'],
            ['attribute_key' => 'cas_latency', 'attribute_value' => 'CL22'],
        ]);

        $sku = $this->addSku($product, 'CRU-16GB-DDR4-3200-UD', 3200, 4500, 3950, 18, 'Rack-A1-Shelf-1');
        $this->addPriceTiers($sku, 3950, 3750, 3500);

        // ── Kingston 8GB DDR4 Laptop SODIMM ────────────
        $product = Product::create([
            'category_id' => $laptopDdr4->id,
            'brand_id' => $kingston->id,
            'name' => 'Kingston ValueRAM 8GB DDR4 3200MHz SODIMM Laptop RAM',
            'short_description' => 'Kingston 8GB laptop memory upgrade. DDR4-3200 SODIMM for smooth laptop performance.',
            'description' => '<p>Kingston ValueRAM 8GB DDR4-3200 SODIMM laptop memory. Easy installation — just open, plug in, and go.</p>',
            'hsn_code' => '84733020',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => 'Kingston Limited Lifetime Warranty',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '8', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'type', 'attribute_value' => 'DDR4'],
            ['attribute_key' => 'speed', 'attribute_value' => '3200', 'attribute_unit' => 'MHz'],
            ['attribute_key' => 'form_factor', 'attribute_value' => 'SODIMM'],
            ['attribute_key' => 'voltage', 'attribute_value' => '1.2', 'attribute_unit' => 'V'],
            ['attribute_key' => 'cas_latency', 'attribute_value' => 'CL22'],
        ]);

        $this->addSku($product, 'KVR-8GB-DDR4-3200-SOD', 1650, 2300, 2050, 30, 'Rack-A1-Shelf-2');

        // ── Corsair Vengeance 16GB DDR4 Desktop ────────
        $product = Product::create([
            'category_id' => $desktopDdr4->id,
            'brand_id' => $corsair->id,
            'name' => 'Corsair Vengeance LPX 16GB DDR4 3200MHz Desktop RAM',
            'short_description' => 'High-performance Corsair Vengeance LPX 16GB with aluminum heat spreader for overclocking.',
            'description' => '<p>Corsair Vengeance LPX 16GB DDR4-3200 with low-profile heat spreader. Designed for high-performance overclocking on Intel and AMD DDR4 motherboards. XMP 2.0 support for automatic overclocking.</p>',
            'hsn_code' => '84733020',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => 'Corsair Limited Lifetime Warranty',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '16', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'type', 'attribute_value' => 'DDR4'],
            ['attribute_key' => 'speed', 'attribute_value' => '3200', 'attribute_unit' => 'MHz'],
            ['attribute_key' => 'form_factor', 'attribute_value' => 'UDIMM'],
            ['attribute_key' => 'voltage', 'attribute_value' => '1.2', 'attribute_unit' => 'V'],
            ['attribute_key' => 'cas_latency', 'attribute_value' => 'CL16'],
            ['attribute_key' => 'heat_spreader', 'attribute_value' => 'Yes'],
        ]);

        $this->addSku($product, 'COR-VEN-16GB-DDR4-3200', 3800, 5200, 4700, 12, 'Rack-A1-Shelf-3');
    }

    private function seedSsdProducts(): void
    {
        $samsung = Brand::where('slug', 'samsung')->first();
        $crucial = Brand::where('slug', 'crucial')->first();
        $kingston = Brand::where('slug', 'kingston')->first();
        $sataSsd = Category::where('slug', 'sata-ssd')->first();
        $nvmeSsd = Category::where('slug', 'nvme-ssd-m2')->first();

        // ── Samsung 870 EVO 500GB SATA SSD ─────────────
        $product = Product::create([
            'category_id' => $sataSsd->id,
            'brand_id' => $samsung->id,
            'name' => 'Samsung 870 EVO 500GB SATA SSD',
            'short_description' => 'Samsung 870 EVO — industry-leading SATA SSD with V-NAND and 560 MB/s read speed.',
            'description' => '<p>The Samsung 870 EVO delivers fast speeds and reliability with the latest V-NAND and a new controller. Upgrade to 2.5" SATA SSD to experience 560 MB/s sequential reads.</p>',
            'hsn_code' => '84717020',
            'gst_rate' => 18.00,
            'warranty_months' => 60,
            'warranty_description' => '5 Years or 300 TBW',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '500', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'interface', 'attribute_value' => 'SATA III 6Gb/s'],
            ['attribute_key' => 'form_factor', 'attribute_value' => '2.5"'],
            ['attribute_key' => 'read_speed', 'attribute_value' => '560', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'write_speed', 'attribute_value' => '530', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'tbw', 'attribute_value' => '300', 'attribute_unit' => 'TB'],
            ['attribute_key' => 'nand_type', 'attribute_value' => 'TLC V-NAND'],
        ]);

        $this->addSku($product, 'SAM-870EVO-500GB', 3200, 4500, 3950, 20, 'Rack-B1-Shelf-1');

        // ── Samsung 980 PRO 1TB NVMe ───────────────────
        $product = Product::create([
            'category_id' => $nvmeSsd->id,
            'brand_id' => $samsung->id,
            'name' => 'Samsung 980 PRO 1TB PCIe Gen4 NVMe M.2 SSD',
            'short_description' => 'Blazing PCIe 4.0 NVMe SSD with 7,000 MB/s read speeds for professionals and gamers.',
            'description' => '<p>Samsung 980 PRO with PCIe 4.0 NVMe delivers read speeds up to 7,000 MB/s — nearly 2x the speed of PCIe 3.0 SSDs. Powered by Samsung V-NAND and the Elpis controller.</p>',
            'hsn_code' => '84717020',
            'gst_rate' => 18.00,
            'warranty_months' => 60,
            'warranty_description' => '5 Years or 600 TBW',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '1', 'attribute_unit' => 'TB'],
            ['attribute_key' => 'interface', 'attribute_value' => 'PCIe Gen4 NVMe'],
            ['attribute_key' => 'form_factor', 'attribute_value' => 'M.2 2280'],
            ['attribute_key' => 'read_speed', 'attribute_value' => '7000', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'write_speed', 'attribute_value' => '5000', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'tbw', 'attribute_value' => '600', 'attribute_unit' => 'TB'],
            ['attribute_key' => 'nand_type', 'attribute_value' => 'TLC V-NAND'],
            ['attribute_key' => 'dram_cache', 'attribute_value' => 'Yes'],
        ]);

        $sku = $this->addSku($product, 'SAM-980PRO-1TB', 6500, 8900, 8100, 10, 'Rack-B1-Shelf-2');
        $this->addPriceTiers($sku, 8100, 7800, 7400);

        // ── Kingston NV2 512GB NVMe ────────────────────
        $product = Product::create([
            'category_id' => $nvmeSsd->id,
            'brand_id' => $kingston->id,
            'name' => 'Kingston NV2 500GB PCIe Gen4 NVMe M.2 SSD',
            'short_description' => 'Budget-friendly Gen4 NVMe SSD with 3,500 MB/s read speeds. Ideal for system upgrades.',
            'description' => '<p>Kingston NV2 delivers Gen 4x4 NVMe performance in a compact M.2 2280 form factor. Great value upgrade from SATA or older NVMe drives.</p>',
            'hsn_code' => '84717020',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => '3 Years or 160 TBW',
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '500', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'interface', 'attribute_value' => 'PCIe Gen4 NVMe'],
            ['attribute_key' => 'form_factor', 'attribute_value' => 'M.2 2280'],
            ['attribute_key' => 'read_speed', 'attribute_value' => '3500', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'write_speed', 'attribute_value' => '2100', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'tbw', 'attribute_value' => '160', 'attribute_unit' => 'TB'],
        ]);

        $this->addSku($product, 'KNG-NV2-500GB', 2200, 3500, 2950, 15, 'Rack-B1-Shelf-3');

        // ── Crucial BX500 480GB SATA ───────────────────
        $product = Product::create([
            'category_id' => $sataSsd->id,
            'brand_id' => $crucial->id,
            'name' => 'Crucial BX500 480GB 2.5" SATA SSD',
            'short_description' => 'Affordable SATA SSD upgrade — 540 MB/s read, perfect for replacing old HDDs.',
            'description' => '<p>Crucial BX500 is the easiest way to get all the speed of a new computer without the price. Boot up in seconds and load files faster.</p>',
            'hsn_code' => '84717020',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => '3 Years or 120 TBW',
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'capacity', 'attribute_value' => '480', 'attribute_unit' => 'GB'],
            ['attribute_key' => 'interface', 'attribute_value' => 'SATA III 6Gb/s'],
            ['attribute_key' => 'form_factor', 'attribute_value' => '2.5"'],
            ['attribute_key' => 'read_speed', 'attribute_value' => '540', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'write_speed', 'attribute_value' => '500', 'attribute_unit' => 'MB/s'],
            ['attribute_key' => 'nand_type', 'attribute_value' => 'TLC 3D NAND'],
        ]);

        $this->addSku($product, 'CRU-BX500-480GB', 2000, 3200, 2750, 22, 'Rack-B1-Shelf-1');
    }

    private function seedM12Products(): void
    {
        $phoenixContact = Brand::where('slug', 'phoenix-contact')->first();
        $turck = Brand::where('slug', 'turck')->first();
        $m12Cat = Category::where('slug', 'm12-connectors')->first();

        // ── M12 4-pin A-coded Male Straight 5m ─────────
        $product = Product::create([
            'category_id' => $m12Cat->id,
            'brand_id' => $phoenixContact->id,
            'name' => 'Phoenix Contact M12 4-Pin A-Coded Male Straight Cable 5m',
            'short_description' => 'M12 A-coded 4-pin male connector with 5m PUR cable. IP67 rated for industrial environments.',
            'description' => '<p>Phoenix Contact M12 sensor/actuator cable with straight male connector. A-coded, 4-pin, 5 meter PUR cable jacket for maximum durability in harsh industrial environments. Suitable for sensors, actuators, and fieldbus connections.</p>',
            'hsn_code' => '85366990',
            'gst_rate' => 18.00,
            'warranty_months' => 12,
            'warranty_description' => '1 Year Manufacturer Warranty',
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'coding', 'attribute_value' => 'A-coded'],
            ['attribute_key' => 'pins', 'attribute_value' => '4'],
            ['attribute_key' => 'gender', 'attribute_value' => 'Male'],
            ['attribute_key' => 'ip_rating', 'attribute_value' => 'IP67'],
            ['attribute_key' => 'cable_length', 'attribute_value' => '5', 'attribute_unit' => 'm'],
            ['attribute_key' => 'connector_type', 'attribute_value' => 'Straight'],
            ['attribute_key' => 'voltage_rating', 'attribute_value' => '250', 'attribute_unit' => 'V'],
            ['attribute_key' => 'current_rating', 'attribute_value' => '4', 'attribute_unit' => 'A'],
            ['attribute_key' => 'shielded', 'attribute_value' => 'No'],
            ['attribute_key' => 'material', 'attribute_value' => 'Nickel-plated brass'],
        ]);

        $sku = $this->addSku($product, 'PHX-M12-4P-A-M-STR-5M', 450, 850, 720, 50, 'Rack-D1-Shelf-1');
        $this->addPriceTiers($sku, 720, 650, 580);

        // ── M12 5-pin D-coded Female Angled 2m ─────────
        $product = Product::create([
            'category_id' => $m12Cat->id,
            'brand_id' => $turck->id,
            'name' => 'Turck M12 5-Pin D-Coded Female Angled Cable 2m',
            'short_description' => 'M12 D-coded 5-pin female right-angle Ethernet connector. 2m shielded cable, IP67.',
            'description' => '<p>Turck M12 Ethernet cable with D-coded 5-pin female angled connector. 100 Mbps Ethernet-rated with Cat5e shielded cable. Right-angle design for tight installation spaces.</p>',
            'hsn_code' => '85366990',
            'gst_rate' => 18.00,
            'warranty_months' => 12,
            'warranty_description' => '1 Year Manufacturer Warranty',
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'coding', 'attribute_value' => 'D-coded'],
            ['attribute_key' => 'pins', 'attribute_value' => '5'],
            ['attribute_key' => 'gender', 'attribute_value' => 'Female'],
            ['attribute_key' => 'ip_rating', 'attribute_value' => 'IP67'],
            ['attribute_key' => 'cable_length', 'attribute_value' => '2', 'attribute_unit' => 'm'],
            ['attribute_key' => 'connector_type', 'attribute_value' => 'Angled (90°)'],
            ['attribute_key' => 'shielded', 'attribute_value' => 'Yes'],
            ['attribute_key' => 'material', 'attribute_value' => 'Nickel-plated brass'],
        ]);

        $sku = $this->addSku($product, 'TUR-M12-5P-D-F-ANG-2M', 520, 950, 820, 35, 'Rack-D1-Shelf-2');
        $this->addPriceTiers($sku, 820, 740, 680);

        // ── M12 8-pin X-coded Male Panel Mount ─────────
        $product = Product::create([
            'category_id' => $m12Cat->id,
            'brand_id' => $phoenixContact->id,
            'name' => 'Phoenix Contact M12 8-Pin X-Coded Male Panel Mount Connector',
            'short_description' => 'M12 X-coded 8-pin 10 Gigabit Ethernet panel-mount connector. IP68 rated, Cat6A.',
            'description' => '<p>Phoenix Contact M12 X-coded panel mount connector for 10 Gigabit Ethernet. 8-pin, Cat6A rated, IP68 protection. For industrial switch cabinets and machine panels.</p>',
            'hsn_code' => '85366990',
            'gst_rate' => 18.00,
            'warranty_months' => 12,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'coding', 'attribute_value' => 'X-coded'],
            ['attribute_key' => 'pins', 'attribute_value' => '8'],
            ['attribute_key' => 'gender', 'attribute_value' => 'Male'],
            ['attribute_key' => 'ip_rating', 'attribute_value' => 'IP68'],
            ['attribute_key' => 'connector_type', 'attribute_value' => 'Panel Mount'],
            ['attribute_key' => 'voltage_rating', 'attribute_value' => '50', 'attribute_unit' => 'V'],
            ['attribute_key' => 'current_rating', 'attribute_value' => '0.5', 'attribute_unit' => 'A'],
            ['attribute_key' => 'shielded', 'attribute_value' => 'Yes'],
            ['attribute_key' => 'material', 'attribute_value' => 'Stainless steel'],
        ]);

        $sku = $this->addSku($product, 'PHX-M12-8P-X-M-PM', 1200, 2100, 1850, 20, 'Rack-D1-Shelf-3');
        $this->addPriceTiers($sku, 1850, 1700, 1550);
    }

    private function seedNetworkingProducts(): void
    {
        $tplink = Brand::where('slug', 'tp-link')->first();
        $unmanagedSw = Category::where('slug', 'unmanaged-switches')->first();

        // ── TP-Link 8-Port Gigabit Switch ──────────────
        $product = Product::create([
            'category_id' => $unmanagedSw->id,
            'brand_id' => $tplink->id,
            'name' => 'TP-Link TL-SG108 8-Port Gigabit Unmanaged Switch',
            'short_description' => 'TP-Link 8-port Gigabit desktop switch. Plug and play, steel case, green technology.',
            'description' => '<p>TP-Link TL-SG108 provides 8 10/100/1000Mbps ports for fast and reliable network expansion. Steel case construction, plug and play operation, and IEEE 802.3az Energy Efficient Ethernet.</p>',
            'hsn_code' => '85176290',
            'gst_rate' => 18.00,
            'warranty_months' => 36,
            'warranty_description' => '3 Year TP-Link Warranty',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($product, [
            ['attribute_key' => 'ports', 'attribute_value' => '8'],
            ['attribute_key' => 'speed', 'attribute_value' => '1Gbps'],
            ['attribute_key' => 'poe', 'attribute_value' => 'None'],
            ['attribute_key' => 'managed', 'attribute_value' => 'No'],
            ['attribute_key' => 'rack_mountable', 'attribute_value' => 'No'],
        ]);

        $this->addSku($product, 'TPL-SG108-8PORT', 1100, 1800, 1550, 15, 'Rack-C1-Shelf-1');

        // ── D-Link Cat6 UTP RJ45 Ethernet Patch Cord ──
        $dlink = Brand::where('slug', 'd-link')->first();
        $cableCat = Category::where('slug', 'network-cables')->first();

        $patchCord = Product::create([
            'category_id' => $cableCat->id,
            'brand_id' => $dlink->id,
            'name' => 'D-Link Cat6 UTP RJ45 Ethernet Patch Cord Cable',
            'short_description' => 'High-performance Cat6 unshielded twisted pair RJ45 patch cord cable for Gigabit LAN networks.',
            'description' => '<p>D-Link 24 AWG Cat6 UTP patch cord with molded snagless RJ45 connectors. 250MHz performance for high-speed Ethernet networking in office and data center environments.</p>',
            'hsn_code' => '85444299',
            'gst_rate' => 18.00,
            'warranty_months' => 12,
            'warranty_description' => '1 Year D-Link Warranty',
            'is_featured' => true,
            'is_express_eligible' => true,
        ]);

        $this->addAttributes($patchCord, [
            ['attribute_key' => 'cable_type', 'attribute_value' => 'Cat6 UTP'],
            ['attribute_key' => 'connector_type', 'attribute_value' => 'RJ45 Male to Male'],
            ['attribute_key' => 'frequency', 'attribute_value' => '250', 'attribute_unit' => 'MHz'],
            ['attribute_key' => 'conductor', 'attribute_value' => '24 AWG Bare Copper'],
        ]);

        $sku1m = $this->addSku($patchCord, 'DLK-CAT6-PC-1M', 60, 150, 110, 100, 'Rack-C2-Shelf-1');
        $this->addPriceTiers($sku1m, 110, 95, 80);

        $sku2m = $this->addSku($patchCord, 'DLK-CAT6-PC-2M', 90, 220, 160, 80, 'Rack-C2-Shelf-1');
        $this->addPriceTiers($sku2m, 160, 140, 120);

        $sku5m = $this->addSku($patchCord, 'DLK-CAT6-PC-5M', 180, 380, 290, 60, 'Rack-C2-Shelf-1');
        $this->addPriceTiers($sku5m, 290, 260, 225);
    }

    // ── Helpers ────────────────────────────────────────

    private function addAttributes(Product $product, array $attributes): void
    {
        foreach ($attributes as $index => $attr) {
            ProductAttribute::create(array_merge($attr, [
                'product_id' => $product->id,
                'is_filterable' => true,
                'is_searchable' => true,
                'sort_order' => $index + 1,
            ]));
        }
    }

    private function addSku(
        Product $product,
        string $sku,
        float $cost,
        float $retail,
        float $selling,
        int $stock,
        string $location
    ): ProductSku {
        return ProductSku::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'cost_price' => $cost,
            'retail_price' => $retail,
            'selling_price' => $selling,
            'stock_quantity' => $stock,
            'reserved_quantity' => 0,
            'min_stock_alert' => 5,
            'stock_location' => $location,
            'is_active' => true,
        ]);
    }

    private function addPriceTiers(ProductSku $sku, float $retailPrice, float $businessPrice, float $bulkPrice): void
    {
        PriceTier::create(['sku_id' => $sku->id, 'tier' => 'retail', 'min_quantity' => 1, 'price' => $retailPrice]);
        PriceTier::create(['sku_id' => $sku->id, 'tier' => 'business', 'min_quantity' => 1, 'price' => $businessPrice]);
        PriceTier::create(['sku_id' => $sku->id, 'tier' => 'bulk', 'min_quantity' => 10, 'price' => $bulkPrice]);
    }
}
