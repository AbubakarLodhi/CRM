<?php

namespace Database\Seeders;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Support\DemoAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    // ─── Merchant to seed for ─────────────────────────────────────
    private string $merchantEmail = 'info@flowdesk.com';

    private ?string $staffEmailNamespace = null;

    public function forMerchant(string $email): void
    {
        $this->merchantEmail = $email;
        $this->staffEmailNamespace = null;

        if (DemoAccount::isTemporaryDemoEmail($email)
            && preg_match('/^demo-([a-f0-9]{32})@/i', $email, $matches) === 1) {
            $this->staffEmailNamespace = substr($matches[1], 0, 8);
        }

        $this->run();
    }

    public function run(): void
    {
        $this->command->info('🚀 Starting DemoSeeder...');

        $merchant = Merchant::where('email', $this->merchantEmail)->first();

        if (! $merchant) {
            $this->command->error("Merchant [{$this->merchantEmail}] not found. Run MerchantsSeeder first.");

            return;
        }

        $this->command->info("✅ Merchant found: {$merchant->name}");

        // Run in order
        $business = $this->seedBusiness($merchant);
        $branches = $this->seedBranches($merchant, $business);
        $roles = $this->seedRoles();
        $staff = $this->seedStaff($merchant, $branches, $roles);
        $categories = $this->seedCategories($merchant);
        [$products, $variants] = $this->seedProducts($merchant, $branches, $categories);
        $customers = $this->seedCustomers($merchant);
        $vendors = $this->seedVendors($merchant);
        $this->seedPurchases($merchant, $business, $branches, $vendors, $products, $variants, $staff);
        $this->seedSales($merchant, $branches, $customers, $products, $variants, $staff);

        $this->command->info('');
        $this->command->info('✅ DemoSeeder completed successfully!');

        if ($this->staffEmailNamespace === null) {
            $this->command->info('─────────────────────────────────────────────');
            $this->command->info('  Login credentials for seeded staff:');
            $this->command->info('  Manager  → manager@demo.com  / password123');
            $this->command->info('  Staff 1  → staff1@demo.com   / password123');
            $this->command->info('  Staff 2  → staff2@demo.com   / password123');
            $this->command->info('─────────────────────────────────────────────');
        }
    }

    private function scopedStaffEmail(string $localPart): string
    {
        if ($this->staffEmailNamespace === null) {
            return "{$localPart}@demo.com";
        }

        return "{$localPart}.{$this->staffEmailNamespace}@demo.com";
    }

    private function demoDocumentSuffix(): string
    {
        if ($this->staffEmailNamespace === null) {
            return '';
        }

        return '-'.$this->staffEmailNamespace;
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. BUSINESS
    // ═══════════════════════════════════════════════════════════════
    private function seedBusiness(Merchant $merchant): Business
    {
        $business = Business::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'name' => 'Flowdesk Demo Business',
            ],
            [
                'id' => Str::uuid()->toString(),
            ]
        );

        $this->command->info("  📦 Business: {$business->name}");

        return $business;
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. BRANCHES (2)
    // ═══════════════════════════════════════════════════════════════
    private function seedBranches(Merchant $merchant, Business $business): array
    {
        $branchData = [
            ['name' => 'Main Branch - Lahore', 'address' => 'Main Boulevard, Lahore'],
            ['name' => 'Branch 2 - Karachi',   'address' => 'Clifton, Karachi'],
        ];

        $branches = [];

        foreach ($branchData as $data) {
            $branch = Branch::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'name' => $data['name'],
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'business_id' => (string) $business->id,
                    'address' => $data['address'],
                    'postal_code' => '54000',
                    'status' => 'active',
                    'is_active' => true,
                ]
            );

            $branches[] = $branch;
            $this->command->info("  🏢 Branch: {$branch->name}");
        }

        return $branches;
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. ROLES (2)
    // ═══════════════════════════════════════════════════════════════
    private function seedRoles(): array
    {
        $roleNames = ['Manager', 'Sales Staff'];
        $roles = [];

        foreach ($roleNames as $name) {
            $role = Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'staff'],
                ['id' => Str::uuid()->toString()]
            );

            // Assign permissions via raw DB to avoid UUID casting issue with Spatie
            if ($name === 'Manager') {
                $permIds = \DB::table('permissions')->where('guard_name', 'staff')->pluck('id');
            } else {
                $permIds = \DB::table('permissions')->where('guard_name', 'staff')
                    ->where(function ($q) {
                        $q->where('name', 'like', 'sales.%')
                            ->orWhere('name', 'like', 'customers.%')
                            ->orWhere('name', 'like', 'products.%')
                            ->orWhere('name', 'like', 'dashboard.%');
                    })->pluck('id');
            }

            \DB::table('role_has_permissions')->where('role_id', (string) $role->id)->delete();
            foreach ($permIds as $permId) {
                \DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => (string) $permId,
                    'role_id' => (string) $role->id,
                ]);
            }

            $roles[$name] = $role;
            $this->command->info("  🔐 Role: {$name}");
        }

        return $roles;
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. STAFF (3)
    // ═══════════════════════════════════════════════════════════════
    private function seedStaff(Merchant $merchant, array $branches, array $roles): array
    {
        $staffData = [
            [
                'name' => 'Demo Manager',
                'email' => $this->scopedStaffEmail('manager'),
                'role' => 'Manager',
                'branch' => 0,
            ],
            [
                'name' => 'Staff Member One',
                'email' => $this->scopedStaffEmail('staff1'),
                'role' => 'Sales Staff',
                'branch' => 0,
            ],
            [
                'name' => 'Staff Member Two',
                'email' => $this->scopedStaffEmail('staff2'),
                'role' => 'Sales Staff',
                'branch' => 1,
            ],
        ];

        $staff = [];

        foreach ($staffData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'merchant_id' => (string) $merchant->id,
                    'is_active' => true,
                    'status' => 'verified',
                    'email_verified_at' => now(),
                ]
            );

            // Assign role via raw DB to avoid guard mismatch
            if (isset($roles[$data['role']])) {
                $roleId = (string) $roles[$data['role']]->id;
                \DB::table('model_has_roles')
                    ->where('model_id', (string) $user->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->delete();
                \DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => (string) $user->id,
                ]);
            }

            // Assign branch — fix: cast UUID to string
            $branch = $branches[$data['branch']];
            $branch->users()->syncWithoutDetaching([(string) $user->id]);

            // Assign all staff permissions directly via raw DB
            $permIds = \DB::table('permissions')->where('guard_name', 'staff')->pluck('id');
            \DB::table('model_has_permissions')
                ->where('model_id', (string) $user->id)
                ->where('model_type', 'App\\Models\\User')
                ->delete();
            foreach ($permIds as $permId) {
                \DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => (string) $permId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => (string) $user->id,
                ]);
            }

            $staff[] = $user;
            $this->command->info("  👤 Staff: {$user->name} ({$data['email']})");
        }

        return $staff;
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. CATEGORIES → BRANDS → MODELS → PRODUCTS → VARIANTS
    // ═══════════════════════════════════════════════════════════════
    private function seedCategories(Merchant $merchant): array
    {
        $categoryData = [
            [
                'name' => 'Inverters',
                'brands' => [
                    ['name' => 'Solis',  'model' => 'Hybrid Series'],
                    ['name' => 'Huawei', 'model' => 'SUN2000 Series'],
                ],
            ],
            [
                'name' => 'Batteries',
                'brands' => [
                    ['name' => 'Pylontech', 'model' => 'US Series'],
                    ['name' => 'Apex',      'model' => 'LiFePO4 Series'],
                ],
            ],
            [
                'name' => 'Solar Panels',
                'brands' => [
                    ['name' => 'TCL',      'model' => 'Mono PERC'],
                    ['name' => 'Canadian', 'model' => 'HiKu Series'],
                ],
            ],
            [
                'name' => 'Cables & Wiring',
                'brands' => [
                    ['name' => 'Finolex', 'model' => 'Solar DC Cable'],
                    ['name' => 'Nexans',  'model' => 'PV1-F Series'],
                ],
            ],
            [
                'name' => 'Accessories',
                'brands' => [
                    ['name' => 'Schneider', 'model' => 'Easy9 Series'],
                    ['name' => 'ABB',       'model' => 'S200 Series'],
                ],
            ],
        ];

        $allCategories = [];

        foreach ($categoryData as $catData) {
            $category = Category::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'name' => $catData['name'],
                ],
                [
                    'id' => Str::uuid()->toString(),
                ]
            );

            $brandsData = [];

            foreach ($catData['brands'] as $brandData) {
                $brandModel = Brand::firstOrCreate(
                    [
                        'merchant_id' => $merchant->id,
                        'name' => $brandData['name'],
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                    ]
                );

                // Fix: pivot table has id and merchant_id columns, insert manually
                $exists = \DB::table('brand_category')
                    ->where('brand_id', (string) $brandModel->id)
                    ->where('category_id', (string) $category->id)
                    ->exists();

                if (! $exists) {
                    \DB::table('brand_category')->insert([
                        'id' => Str::uuid()->toString(),
                        'brand_id' => (string) $brandModel->id,
                        'category_id' => (string) $category->id,
                        'merchant_id' => (string) $merchant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $model = BrandModel::firstOrCreate(
                    [
                        'brand_id' => (string) $brandModel->id,
                        'name' => $brandData['model'],
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'merchant_id' => (string) $merchant->id,
                    ]
                );

                $brandsData[] = [
                    'brand' => $brandModel,
                    'model' => $model,
                ];
            }

            // Use stdClass to avoid Eloquent dynamic property restriction
            $entry = new \stdClass;
            $entry->id = $category->id;
            $entry->name = $category->name;
            $entry->brands_data = $brandsData;

            $allCategories[] = $entry;
            $this->command->info("  📂 Category: {$category->name}");
        }

        return $allCategories;
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. PRODUCTS (2 per model, 2 variants each)
    // ═══════════════════════════════════════════════════════════════
    private function seedProducts(Merchant $merchant, array $branches, array $categories): array
    {
        $allProducts = [];
        $allVariants = [];

        $productSuffixes = [
            'Inverters' => [['5kw Single Phase', 8000, 9500], ['10kw Three Phase', 18000, 21000]],
            'Batteries' => [['5kwh Lithium', 45000, 52000], ['10kwh Lithium', 85000, 95000]],
            'Solar Panels' => [['620W Mono PERC', 12000, 14000], ['720W Bifacial', 16000, 18500]],
            'Cables & Wiring' => [['4mm DC Cable 100m', 3500, 4200], ['6mm DC Cable 100m', 5500, 6500]],
            'Accessories' => [['MCB 32A', 800, 1100], ['Surge Protector SPD', 2500, 3200]],
        ];

        foreach ($categories as $category) {
            $suffixes = $productSuffixes[$category->name] ?? [
                ['Standard Model A', 5000, 6000],
                ['Standard Model B', 8000, 9500],
            ];

            foreach ($category->brands_data as $brandData) {
                $brand = $brandData['brand'];
                $model = $brandData['model'];

                foreach ($suffixes as $index => [$suffix, $purchasePrice, $sellingPrice]) {
                    $productName = "{$brand->name} {$suffix}";
                    $sku = strtoupper(substr($brand->name, 0, 3)).'-'.($index + 1).'-'.strtoupper(Str::random(4));

                    $product = Product::firstOrCreate(
                        [
                            'merchant_id' => $merchant->id,
                            'sku' => $sku,
                        ],
                        [
                            'id' => Str::uuid()->toString(),
                            'name' => $productName,
                            'brand_model_id' => (string) $model->id,
                            'category_id' => (string) $category->id,
                            'purchase_price' => $purchasePrice,
                            'selling_price' => $sellingPrice,
                            'is_active' => true,
                        ]
                    );

                    // Update category_id if product already exists but has no category
                    if (! $product->category_id) {
                        $product->update(['category_id' => (string) $category->id]);
                    }

                    // Fix: cast UUIDs to string
                    foreach ($branches as $branch) {
                        $branch->products()->syncWithoutDetaching([(string) $product->id]);
                        $product->branches()->syncWithoutDetaching([(string) $branch->id]);
                    }

                    // Create 2 variants per product
                    $variantSpecs = [
                        ['Standard', $sellingPrice],
                        ['Premium',  round($sellingPrice * 1.15, 2)],
                    ];

                    foreach ($variantSpecs as [$variantName, $variantPrice]) {
                        $variantSku = $sku.'-'.strtoupper(substr($variantName, 0, 3));

                        $variant = ProductVariant::firstOrCreate(
                            [
                                'product_id' => (string) $product->id,
                                'name' => $variantName,
                            ],
                            [
                                'id' => Str::uuid()->toString(),
                                'merchant_id' => (string) $merchant->id,
                                'sku' => $variantSku,
                                'selling_price' => $variantPrice,
                                'purchase_price' => $purchasePrice,
                                'is_active' => true,
                            ]
                        );

                        $allVariants[] = $variant;
                    }

                    // Generate and attach a placeholder image for the product
                    if (! $product->productImage) {
                        $this->attachPlaceholderImage($product, $category->name);
                    }

                    $allProducts[] = $product;
                }
            }
        }

        $this->command->info('  🛍️  Products created: '.count($allProducts));
        $this->command->info('  🔖  Variants created: '.count($allVariants));

        return [$allProducts, $allVariants];
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER: Generate placeholder image for product
    // ═══════════════════════════════════════════════════════════════
    private function attachPlaceholderImage(Product $product, string $categoryName): void
    {
        $colors = [
            'Inverters' => ['bg' => [29, 158, 117],  'text' => [255, 255, 255]],
            'Batteries' => ['bg' => [59, 130, 246],  'text' => [255, 255, 255]],
            'Solar Panels' => ['bg' => [245, 158, 11],  'text' => [255, 255, 255]],
            'Cables & Wiring' => ['bg' => [239, 68, 68],   'text' => [255, 255, 255]],
            'Accessories' => ['bg' => [139, 92, 246],  'text' => [255, 255, 255]],
        ];

        $color = $colors[$categoryName] ?? ['bg' => [107, 114, 128], 'text' => [255, 255, 255]];

        $initials = collect(preg_split('/\s+/', trim($product->name)) ?: [])
            ->take(2)
            ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
            ->implode('');

        // Create PNG using GD
        $size = 400;
        $img = imagecreatetruecolor($size, $size);

        $bg = imagecolorallocate($img, ...$color['bg']);
        $fg = imagecolorallocate($img, ...$color['text']);

        imagefill($img, 0, 0, $bg);

        // Draw initials text centered
        $fontSize = 5; // built-in font size (1-5)
        $charWidth = imagefontwidth($fontSize);
        $charHeight = imagefontheight($fontSize);
        $textWidth = strlen($initials) * $charWidth * 6;
        $x = ($size - $textWidth) / 2;
        $y = ($size - $charHeight * 6) / 2;

        // Scale up by drawing each char
        $scale = 6;
        imagestring($img, $fontSize, (int) (($size - strlen($initials) * imagefontwidth($fontSize) * $scale) / 2),
            (int) (($size - imagefontheight($fontSize) * $scale) / 2), $initials, $fg);

        // Use imagestring with large font
        $font = 5;
        $tw = strlen($initials) * imagefontwidth($font);
        $th = imagefontheight($font);
        $px = (int) (($size - $tw) / 2);
        $py = (int) (($size - $th) / 2);
        imagestring($img, $font, $px, $py, $initials, $fg);

        $directory = 'products/images';
        $filename = Str::uuid()->toString().'.png';
        $path = $directory.'/'.$filename;
        $fullPath = storage_path('app/public/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        imagepng($img, $fullPath);
        imagedestroy($img);

        $product->productImage()->create([
            'merchant_id' => $product->merchant_id,
            'type' => AttachmentType::IMAGE,
            'meta_type' => AttachmentMetaType::PRODUCT_IMAGE,
            'photo_url' => $path,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. CUSTOMERS (5)
    // ═══════════════════════════════════════════════════════════════
    private function seedCustomers(Merchant $merchant): array
    {
        $customerData = [
            ['name' => 'Ahmad Solar Solutions', 'phone' => '+923001111001', 'email' => 'ahmad.solar@demo.com'],
            ['name' => 'Bilal Energy Systems',  'phone' => '+923001111002', 'email' => 'bilal.energy@demo.com'],
            ['name' => 'Chaudhry Power Works',  'phone' => '+923001111003', 'email' => 'chaudhry.power@demo.com'],
            ['name' => 'Danish Tech Store',     'phone' => '+923001111004', 'email' => 'danish.tech@demo.com'],
            ['name' => 'Farooq Enterprises',    'phone' => '+923001111005', 'email' => 'farooq.ent@demo.com'],
        ];

        $customers = [];

        // Ensure Pakistan exists in countries table
        $pakistan = \DB::table('countries')->where('name', 'Pakistan')->first();
        if (! $pakistan) {
            $pakistanId = Str::uuid()->toString();
            \DB::table('countries')->insert([
                'id' => $pakistanId,
                'name' => 'Pakistan',
                'code' => 'PK',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $pakistanId = $pakistan->id;
        }

        // Ensure Lahore exists in cities table
        $lahore = \DB::table('cities')->where('name', 'Lahore')->where('country_id', $pakistanId)->first();
        if (! $lahore) {
            $lahoreId = Str::uuid()->toString();
            \DB::table('cities')->insert([
                'id' => $lahoreId,
                'name' => 'Lahore',
                'country_id' => $pakistanId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $lahoreId = $lahore->id;
        }

        foreach ($customerData as $data) {
            $customer = Customer::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'email' => $data['email'],
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => 'Lahore, Pakistan',
                    'country_id' => $pakistanId,
                    'city_id' => $lahoreId,
                ]
            );

            $customers[] = $customer;
            $this->command->info("  👥 Customer: {$customer->name}");
        }

        return $customers;
    }

    // ═══════════════════════════════════════════════════════════════
    // 8. VENDORS (5)
    // ═══════════════════════════════════════════════════════════════
    private function seedVendors(Merchant $merchant): array
    {
        $vendorData = [
            ['name' => 'AU Solar Suppliers',   'phone' => '+923002221001', 'email' => 'au.solar@vendor.com'],
            ['name' => 'Apex Battery Depot',   'phone' => '+923002221002', 'email' => 'apex.battery@vendor.com'],
            ['name' => 'Prime Panel Traders',  'phone' => '+923002221003', 'email' => 'prime.panel@vendor.com'],
            ['name' => 'Madina Cable House',   'phone' => '+923002221004', 'email' => 'madina.cable@vendor.com'],
            ['name' => 'National Accessories', 'phone' => '+923002221005', 'email' => 'national.acc@vendor.com'],
        ];

        $vendors = [];

        // Ensure Pakistan + Lahore exist (reuse or re-fetch)
        $pakistanV = \DB::table('countries')->where('name', 'Pakistan')->first();
        if (! $pakistanV) {
            $pakistanVId = Str::uuid()->toString();
            \DB::table('countries')->insert([
                'id' => $pakistanVId,
                'name' => 'Pakistan',
                'code' => 'PK',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $pakistanVId = $pakistanV->id;
        }

        $lahoreV = \DB::table('cities')->where('name', 'Lahore')->where('country_id', $pakistanVId)->first();
        if (! $lahoreV) {
            $lahoreVId = Str::uuid()->toString();
            \DB::table('cities')->insert([
                'id' => $lahoreVId,
                'name' => 'Lahore',
                'country_id' => $pakistanVId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $lahoreVId = $lahoreV->id;
        }

        foreach ($vendorData as $data) {
            $vendor = Vendor::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'email' => $data['email'],
                ],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => 'Lahore, Pakistan',
                    'country_id' => $pakistanVId,
                    'city_id' => $lahoreVId,
                ]
            );

            $vendors[] = $vendor;
            $this->command->info("  🏭 Vendor: {$vendor->name}");
        }

        return $vendors;
    }

    // ═══════════════════════════════════════════════════════════════
    // 9. PURCHASES (2)
    // ═══════════════════════════════════════════════════════════════
    private function seedPurchases(
        Merchant $merchant,
        Business $business,
        array $branches,
        array $vendors,
        array $products,
        array $variants,
        array $staff
    ): void {
        $purchaseConfigs = [
            [
                'vendor' => $vendors[0],
                'branch' => $branches[0],
                'created_by' => $staff[0],
                'days_ago' => 10,
                'product_count' => 3,
            ],
            [
                'vendor' => $vendors[1],
                'branch' => $branches[1],
                'created_by' => $staff[1],
                'days_ago' => 5,
                'product_count' => 2,
            ],
        ];

        foreach ($purchaseConfigs as $i => $config) {
            $purchaseDate = now()->subDays($config['days_ago']);
            $purchaseNo = 'PUR-'.$purchaseDate->format('Ymd').'-DEMO'.($i + 1).$this->demoDocumentSuffix();

            if (Purchase::query()
                ->where('merchant_id', $merchant->id)
                ->where('purchase_no', $purchaseNo)
                ->exists()) {
                $this->command->info("  ⏭️  Purchase {$purchaseNo} already exists, skipping.");

                continue;
            }

            $purchase = Purchase::create([
                'id' => Str::uuid()->toString(),
                'purchase_no' => $purchaseNo,
                'merchant_id' => (string) $merchant->id,
                'vendor_id' => (string) $config['vendor']->id,
                'purchase_date' => $purchaseDate,
                'subtotal' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'payment_type' => 'cash',
                'notes' => 'Demo purchase #'.($i + 1),
                'created_by' => (string) $config['created_by']->id,
            ]);

            $subtotal = 0;
            $selectedProducts = array_slice($products, $i * 3, $config['product_count']);

            foreach ($selectedProducts as $product) {
                $qty = rand(2, 5);
                $unitPrice = $product->purchase_price ?? 10000;
                $lineTotal = $qty * $unitPrice;
                $subtotal += $lineTotal;

                $purchaseItem = PurchaseItem::create([
                    'id' => Str::uuid()->toString(),
                    'purchase_id' => (string) $purchase->id,
                    'business_id' => (string) $business->id,
                    'branch_id' => (string) $config['branch']->id,
                    'product_id' => (string) $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'discount' => 0,
                    'tax' => 0,
                ]);

                $variant = $product->variants()->first();
                if ($variant) {
                    PurchaseItemVariant::create([
                        'id' => Str::uuid()->toString(),
                        'purchase_item_id' => (string) $purchaseItem->id,
                        'product_variant_id' => (string) $variant->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);
                }
            }

            $purchase->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'paid_amount' => $subtotal,
                'due_amount' => 0,
            ]);

            $this->command->info("  🛒 Purchase: {$purchaseNo} — PKR ".number_format($subtotal));
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 10. SALES (2)
    // ═══════════════════════════════════════════════════════════════
    private function seedSales(
        Merchant $merchant,
        array $branches,
        array $customers,
        array $products,
        array $variants,
        array $staff
    ): void {
        $saleConfigs = [
            [
                'customer' => $customers[0],
                'branch' => $branches[0],
                'created_by' => $staff[0],
                'days_ago' => 7,
                'product_count' => 2,
            ],
            [
                'customer' => $customers[1],
                'branch' => $branches[1],
                'created_by' => $staff[2],
                'days_ago' => 3,
                'product_count' => 3,
            ],
        ];

        foreach ($saleConfigs as $i => $config) {
            $saleDate = now()->subDays($config['days_ago']);
            $saleNo = 'SAL-'.$saleDate->format('Ymd').'-DEMO'.($i + 1).$this->demoDocumentSuffix();

            if (Sale::query()
                ->where('merchant_id', $merchant->id)
                ->where('sale_no', $saleNo)
                ->exists()) {
                $this->command->info("  ⏭️  Sale {$saleNo} already exists, skipping.");

                continue;
            }

            $sale = Sale::create([
                'id' => Str::uuid()->toString(),
                'sale_no' => $saleNo,
                'merchant_id' => (string) $merchant->id,
                'customer_id' => (string) $config['customer']->id,
                'sale_date' => $saleDate,
                'subtotal' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'notes' => 'Demo sale #'.($i + 1),
                'created_by' => (string) $config['created_by']->id,
            ]);

            $subtotal = 0;
            $selectedProducts = array_slice($products, $i * 2, $config['product_count']);

            foreach ($selectedProducts as $product) {
                $qty = rand(1, 3);
                $unitPrice = $product->selling_price ?? 12000;
                $lineTotal = $qty * $unitPrice;
                $subtotal += $lineTotal;

                $branch = $config['branch'];
                $business = $branch->business;

                $saleItem = SaleItem::create([
                    'id' => Str::uuid()->toString(),
                    'sale_id' => (string) $sale->id,
                    'business_id' => (string) $business->id,
                    'branch_id' => (string) $branch->id,
                    'product_id' => (string) $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'discount' => 0,
                    'tax' => 0,
                ]);

                $variant = $product->variants()->first();
                if ($variant) {
                    SaleItemVariant::create([
                        'id' => Str::uuid()->toString(),
                        'sale_item_id' => (string) $saleItem->id,
                        'product_variant_id' => (string) $variant->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);
                }
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'paid_amount' => $subtotal,
                'due_amount' => 0,
            ]);

            Order::create([
                'id' => Str::uuid()->toString(),
                'merchant_id' => (string) $merchant->id,
                'sale_id' => (string) $sale->id,
                'status' => 'pending',
            ]);

            $this->command->info("  💰 Sale: {$saleNo} — PKR ".number_format($subtotal));
        }
    }
}
