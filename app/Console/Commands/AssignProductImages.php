<?php

namespace App\Console\Commands;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Models\Product;
use Illuminate\Console\Command;

class AssignProductImages extends Command
{
    protected $signature   = 'products:assign-images';
    protected $description = 'Assign Supabase images to products via attachments table';

    // Map: Supabase filename => partial product name to match
        private array $imageMap = [
        'ABB MCB 32A.jpg'                    => 'ABB MCB 32A',
        'abb surge protector SPD.jfif'       => 'ABB Surge Protector SPD',
        'Apex 5kwh Lithinum.png'             => 'Apex 5kwh Lithium',
        'apex 10kwh lethenium.jfif'          => 'Apex 10kwh Lithium',
        'Canadian 620W Mono Perc.jfif'       => 'Canadian 620W Mono PERC',
        'Canadian 720W Bifacial.jfif'        => 'Canadian 720W Bifacial',
        'finolex 4mm DC cable 100m.jpeg'     => 'Finolex 4mm DC Cable 100m',
        'finolex 6mm DC cable 100m.jpg'      => 'Finolex 6mm DC Cable 100m',
        'Huawei 5kw Single Phase.png'        => 'Huawei 5kw Single Phase',
        'Huawei 10kw Three Phase.jpg'        => 'Huawei 10kw Three Phase',
        'nexans 4mm DC cable 100m.png'       => 'Nexans 4mm DC Cable 100m',
        'nexans 6mm DC cable 100m.jfif'      => 'Nexans 6mm DC Cable 100m',
        'pylontech 5kwh lethenium.jpg'       => 'Pylontech 5kwh Lithium',
        'Pylontech 10kwh Lithium.jpg'        => 'Pylontech 10kwh Lithium',
        'schneider MCB 32A.jfif'             => 'Schneider MCB 32A',
        'schneider surge protector SPD.jfif' => 'Schneider Surge Protector SPD',
        'Solis 5kw Single Phase.jpg'         => 'Solis 5kw Single Phase',
        'solis 10kw three phase.jpg'         => 'Solis 10kw Three Phase',
        'TCL 620W Mono PERC.jfif'            => 'TCL 620W Mono PERC',
        'TCL 720W Bifacial.jfif'             => 'TCL 720W Bifacial',
        'Tester.jfif'                            => 'Tester',
    ];

    // Your Supabase public URL
    private string $supabaseUrl = 'https://hdojyhoqzioxnbkuxjno.supabase.co/storage/v1/object/public/product-images/products';

    public function handle(): void
    {
        $this->info('Starting product image assignment...');
        $assigned = 0;
        $notFound = 0;

        foreach ($this->imageMap as $filename => $productName) {

            // Find product by name (partial match)
            $product = Product::where('name', 'LIKE', '%' . $productName . '%')
                ->orWhere('name', 'LIKE', '%' . str_replace(' ', '%', $productName) . '%')
                ->first();

            if (! $product) {
                $this->warn("❌ Product not found: {$productName}");
                $notFound++;
                continue;
            }

            // Build the Supabase image URL path
            // Store just the relative path in photo_url
            $photoUrl = 'products/' . rawurlencode($filename);

            // Delete existing image
            $product->productImage()?->delete();

            // Create new attachment
            $product->productImage()->create([
                'merchant_id' => $product->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PRODUCT_IMAGE,
                'photo_url'   => $photoUrl,
            ]);

            $this->info("✅ Assigned: {$product->name} => {$filename}");
            $assigned++;
        }

        $this->newLine();
        $this->info("Done! Assigned: {$assigned} | Not found: {$notFound}");
    }
}