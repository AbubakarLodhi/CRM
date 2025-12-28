<?php

namespace Database\Seeders\ZGN;

use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNSolarCategoriesSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        if (!$merchant) return;

        // Helper
        $create = function (string $name, ?string $parentId = null) use ($merchant) {
            return Category::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'parent_id' => $parentId,
                    'name' => $name,
                ],
                [
                    'id' => Str::uuid(),
                ]
            );
        };

        /* ================= ROOT ================= */

        $electronics = $create('Electronics');
        $electrical = $create('Electrical Infrastructure');
        $storage = $create('Energy Storage');
        $mechanical = $create('Mechanical & Structural');
        $safety = $create('Safety & Protection');
        $monitoring = $create('Monitoring & Smart Systems');
        $tools = $create('Installation Tools & Equipment');
        $consumables = $create('Consumables & Accessories');
        $services = $create('Services');
        $systems = $create('Solar Systems');

        /* ================= ELECTRONICS ================= */

        $panels = $create('Solar Panels', $electronics->id);
        foreach (['Monocrystalline', 'Polycrystalline', 'Bifacial', 'Thin Film'] as $type) {
            $create($type, $panels->id);
        }

        $inverters = $create('Inverters', $electronics->id);
        foreach (['On-Grid', 'Off-Grid', 'Hybrid', 'String', 'Micro-Inverter'] as $type) {
            $create($type, $inverters->id);
        }

        $powerElectronics = $create('Power Electronics', $electronics->id);
        $create('DC Optimizers', $powerElectronics->id);

        /* ================= ELECTRICAL ================= */

        $dcSide = $create('DC Side', $electrical->id);
        foreach (['DC Cables', 'DC Combiner Boxes', 'DC Isolators'] as $item) {
            $create($item, $dcSide->id);
        }

        $acSide = $create('AC Side', $electrical->id);
        foreach (['AC Cables', 'AC Distribution Boards', 'Changeover Switches'] as $item) {
            $create($item, $acSide->id);
        }

        /* ================= ENERGY STORAGE ================= */

        $batteries = $create('Batteries', $storage->id);
        foreach (['Lithium-Ion', 'LiFePO4', 'Lead Acid'] as $type) {
            $create($type, $batteries->id);
        }

        $batteryAccessories = $create('Battery Accessories', $storage->id);
        foreach (['Battery Racks', 'BMS'] as $item) {
            $create($item, $batteryAccessories->id);
        }

        /* ================= MECHANICAL ================= */

        $structures = $create('Mounting Structures', $mechanical->id);
        foreach (['L1 Structure', 'L2 Structure', 'L3 Structure', 'Custom Fabricated'] as $type) {
            $create($type, $structures->id);
        }

        $components = $create('Structural Components', $mechanical->id);
        foreach (['Rails', 'Clamps', 'Fasteners'] as $item) {
            $create($item, $components->id);
        }

        /* ================= SAFETY ================= */

        $circuit = $create('Circuit Protection', $safety->id);
        foreach (['DC MCB / MCCB', 'AC MCB / MCCB', 'Fuses'] as $item) {
            $create($item, $circuit->id);
        }

        $surge = $create('Surge Protection', $safety->id);
        foreach (['DC SPD', 'AC SPD'] as $item) {
            $create($item, $surge->id);
        }

        $earthing = $create('Earthing', $safety->id);
        foreach (['Earthing Rods', 'Earthing Pits'] as $item) {
            $create($item, $earthing->id);
        }

        /* ================= MONITORING ================= */

        $devices = $create('Monitoring Devices', $monitoring->id);
        foreach (['Energy Meters', 'Net Meters'] as $item) {
            $create($item, $devices->id);
        }

        $comms = $create('Communication', $monitoring->id);
        foreach (['WiFi Loggers', 'GSM Loggers'] as $item) {
            $create($item, $comms->id);
        }

        /* ================= TOOLS ================= */

        foreach (['Electrical Tools', 'Mechanical Tools', 'Safety Gear'] as $item) {
            $create($item, $tools->id);
        }

        /* ================= CONSUMABLES ================= */

        foreach (['Cable Ties', 'Insulation Tape', 'Warning Labels'] as $item) {
            $create($item, $consumables->id);
        }

        /* ================= SERVICES ================= */

        foreach (['Installation', 'Net Metering Processing', 'AMC'] as $item) {
            $create($item, $services->id);
        }

        /* ================= SYSTEMS ================= */

        foreach (['Residential Systems', 'Commercial Systems'] as $item) {
            $create($item, $systems->id);
        }
    }
}
