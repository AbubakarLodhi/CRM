<?php

$name = config('branding.name');

return [

    'meta' => [
        'title' => "{$name} — CRM, POS, Inventory & Sales in One Platform",
        'description' => 'Run sales, inventory, purchases, customers, reports, and notifications from one modern CRM. Try the live demo instantly — no signup required.',
        'keywords' => 'CRM, POS, inventory management, sales tracking, business software, Flowdesk',
        'og_image' => 'images/landing/og-cover.svg',
    ],

    'nav' => [
        ['label' => 'Features', 'href' => '#features'],
        ['label' => 'Demo', 'href' => '#demo'],
        ['label' => 'Pricing', 'href' => '#pricing'],
        ['label' => 'Testimonials', 'href' => '#testimonials'],
        ['label' => 'FAQ', 'href' => '#faq'],
        ['label' => 'Contact', 'href' => '#contact'],
    ],

    'hero' => [
        'headline' => 'Run your entire business from one intelligent CRM',
        'tagline' => 'Sales, inventory, purchases, customers, payroll, assets, and reports — unified in a fast, modern workspace built for growing merchants.',
        'metrics' => [
            ['value' => 12, 'suffix' => '+', 'label' => 'Core modules'],
            ['value' => 3, 'suffix' => 'x', 'label' => 'Faster checkout'],
            ['value' => 99.9, 'suffix' => '%', 'label' => 'Uptime target'],
        ],
        'video' => [
            'src' => 'videos/flowdesk-walkthrough.mp4',
            'poster' => 'images/landing/dashboard.png',
            'caption' => 'See the full platform in action',
        ],
    ],

    'why' => [
        [
            'icon' => 'layers',
            'title' => 'All-in-one operations',
            'description' => 'Stop juggling spreadsheets. Manage products, sales, purchases, and customers from a single panel.',
        ],
        [
            'icon' => 'zap',
            'title' => 'Built for speed',
            'description' => 'POS checkout, variant search, and branch-aware stock keep your team moving fast.',
        ],
        [
            'icon' => 'shield',
            'title' => 'Secure & role-based',
            'description' => 'Granular permissions, audit trails, and staff roles protect your business data.',
        ],
    ],

    'feature_categories' => [
        'sales' => [
            'label' => 'Sales & POS',
            'features' => [
                [
                    'name' => 'Point of Sale',
                    'summary' => 'Fast checkout with product search, variants, and branch selection.',
                    'image' => 'images/landing/pos.png',
                    'bullets' => ['Barcode-ready product search', 'Multi-branch stock', 'Credit & cash sales'],
                ],
                [
                    'name' => 'Sales Management',
                    'summary' => 'Track every sale, return, and invoice with full line-item detail.',
                    'image' => 'images/landing/sales.png',
                    'bullets' => ['Sale returns & refunds', 'Dynamic invoice fields', 'Customer credit tracking'],
                ],
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'features' => [
                [
                    'name' => 'Products & Variants',
                    'summary' => 'Organize catalog with categories, brands, models, and variant options.',
                    'image' => 'images/landing/products.png',
                    'bullets' => ['Variant-level stock', 'Category trees', 'Product images'],
                ],
                [
                    'name' => 'Purchases',
                    'summary' => 'Record purchases, manage vendors, and track procurement end-to-end.',
                    'image' => 'images/landing/purchases.png',
                    'bullets' => ['Purchase returns', 'Vendor ledger', 'Multi-item receipts'],
                ],
            ],
        ],
        'insights' => [
            'label' => 'Insights & Automation',
            'features' => [
                [
                    'name' => 'Reports & Analytics',
                    'summary' => 'Dashboard widgets, stock reports, sales summaries, and cash flow views.',
                    'image' => 'images/landing/stock-report.png',
                    'bullets' => ['Stock report by variant', 'Sales & purchase summaries', 'Export to Excel'],
                ],
                [
                    'name' => 'Reminders & Notifications',
                    'summary' => 'CRM-style customer profiles with WhatsApp and email reminders.',
                    'image' => 'images/landing/notifications.png',
                    'bullets' => ['Credit sale reminders', 'Template-based alerts', 'Customer sales history'],
                ],
            ],
        ],
    ],

    'demo' => [
        'headline' => 'Explore the full product — no signup needed',
        'description' => 'Click once to launch your own private demo. No email, no password — we create a temporary account for you and sign you in automatically.',
        'bullets' => [
            'One-click access — no credentials to remember',
            'Pre-loaded with products, sales, purchases, customers, and vendors',
            'Your own private sandbox — edit freely, then everything is deleted when time ends',
        ],
        'video' => [
            'src' => 'videos/flowdesk-walkthrough.mp4',
            'poster' => 'images/landing/dashboard.png',
        ],
    ],

    'use_cases' => [
        'headline' => 'Built for how you work',
        'description' => 'Whether you run one shop or five branches, Flowdesk adapts to the way your team operates.',
        'items' => [
            [
                'icon' => 'retail',
                'tag' => 'Retail',
                'title' => 'Retail & electronics',
                'description' => 'High-volume checkout with variant-level stock that stays accurate after every sale.',
                'before' => 'Manual stock counts & paper receipts',
                'after' => 'Live inventory with touch-friendly POS',
                'highlights' => ['Variant search', 'Fast checkout', 'Printed receipts'],
            ],
            [
                'icon' => 'wholesale',
                'tag' => 'Wholesale',
                'title' => 'Wholesale distribution',
                'description' => 'Track vendors, purchase orders, and landed costs in one connected ledger.',
                'before' => 'Scattered vendor spreadsheets',
                'after' => 'Centralized purchases & vendor history',
                'highlights' => ['Vendor ledger', 'Purchase orders', 'Cost tracking'],
            ],
            [
                'icon' => 'branches',
                'tag' => 'Multi-branch',
                'title' => 'Multi-branch operations',
                'description' => 'See stock, sales, and performance for every location without switching tools.',
                'before' => 'No branch-level visibility',
                'after' => 'Branch-aware stock & reporting',
                'highlights' => ['Per-branch stock', 'Unified dashboard', 'Role-based access'],
            ],
        ],
    ],

    'pricing' => [
        [
            'name' => 'Starter',
            'monthly' => '$29',
            'yearly' => '$290',
            'description' => 'For solo merchants getting started.',
            'features' => ['1 branch', 'POS & sales', 'Basic reports', 'Email support'],
            'popular' => false,
        ],
        [
            'name' => 'Growth',
            'monthly' => '$79',
            'yearly' => '$790',
            'description' => 'For teams scaling operations.',
            'features' => ['Up to 5 branches', 'All modules', 'WhatsApp notifications', 'Priority support'],
            'popular' => true,
        ],
        [
            'name' => 'Enterprise',
            'monthly' => 'Custom',
            'yearly' => 'Custom',
            'description' => 'For large or custom deployments.',
            'features' => ['Unlimited branches', 'Custom integrations', 'Dedicated onboarding', 'SLA'],
            'popular' => false,
        ],
    ],

    'testimonials' => [
        [
            'quote' => "{$name} replaced three tools for us. POS is fast, and our team finally has one source of truth for stock.",
            'author' => 'Ahmed K.',
            'role' => 'Store Owner, Karachi',
            'stars' => 5,
        ],
        [
            'quote' => 'Credit reminders and customer history alone saved us hours every week. The dashboard is exactly what we needed.',
            'author' => 'Sara M.',
            'role' => 'Operations Manager',
            'stars' => 5,
        ],
        [
            'quote' => 'We onboarded staff in a day thanks to clear roles and permissions. Reporting is miles ahead of our old setup.',
            'author' => 'Bilal R.',
            'role' => 'Retail Director',
            'stars' => 5,
        ],
    ],

    'faq' => [
        'General' => [
            ['q' => "What is {$name}?", 'a' => "{$name} is a Laravel-powered CRM for merchants — sales, inventory, purchases, customers, payroll, assets, and reporting in one panel."],
            ['q' => 'Can I try before buying?', 'a' => 'Yes. Use the Try Demo button to explore the full merchant panel with sample data — no credit card required.'],
        ],
        'Demo' => [
            ['q' => 'Do I need login credentials for the demo?', 'a' => 'No. Click Try Demo and you are signed in automatically. We create a temporary private account behind the scenes — you never see or enter an email or password.'],
            ['q' => 'What happens when my demo time ends?', 'a' => 'Your temporary account and everything you created during the session — products, sales, customers, and all other data — are permanently deleted. Other visitors never see your data.'],
            ['q' => 'How long does a demo session last?', 'a' => 'Each device gets 30 minutes total. If you exit and return before time runs out, the countdown continues. Once expired, you cannot start a new demo until access resets daily at 5:00 PM.'],
        ],
        'Pricing' => [
            ['q' => 'Is there a free trial?', 'a' => 'The live demo is always free. Paid plans include a trial period — contact us for details on Enterprise.'],
            ['q' => 'Can I migrate my existing data?', 'a' => 'We support onboarding assistance for Growth and Enterprise plans. Reach out via the contact form.'],
        ],
    ],

    'integrations' => ['WhatsApp', 'Email', 'PDF Invoices', 'S3 Storage', 'Role Permissions', 'Audit Log', 'Multi-branch', 'Dark Mode'],

    'security' => [
        ['title' => 'Encrypted sessions', 'description' => 'Secure authentication with role-based access control.'],
        ['title' => 'Audit trail', 'description' => 'Track changes across critical business records.'],
        ['title' => 'Staff permissions', 'description' => 'Fine-grained module and action permissions.'],
        ['title' => 'Data isolation', 'description' => 'Each merchant\'s data is fully isolated.'],
    ],

    'contact' => [
        'email' => config('branding.primary_merchant_email'),
        'website' => config('branding.primary_merchant_website'),
        'web3forms_access_key' => env('WEB3FORMS_ACCESS_KEY'),
        'form_subject' => 'New Flowdesk contact form message',
    ],

    'footer' => [
        'columns' => [
            [
                'title' => 'Product',
                'links' => [
                    ['label' => 'Features', 'href' => '#features'],
                    ['label' => 'Live Demo', 'href' => '#demo'],
                    ['label' => 'Use Cases', 'href' => '#use-cases'],
                    ['label' => 'Pricing', 'href' => '#pricing'],
                ],
            ],
            [
                'title' => 'Resources',
                'links' => [
                    ['label' => 'Testimonials', 'href' => '#testimonials'],
                    ['label' => 'FAQ', 'href' => '#faq'],
                    ['label' => 'Contact', 'href' => '#contact'],
                ],
            ],
            [
                'title' => 'Account',
                'links' => [
                    ['label' => 'Try Demo', 'route' => 'demo.login'],
                    ['label' => 'Merchant Login', 'route' => 'filament.merchant.auth.login'],
                    ['label' => 'Staff Login', 'route' => 'filament.user.auth.login'],
                ],
            ],
        ],
    ],

];
