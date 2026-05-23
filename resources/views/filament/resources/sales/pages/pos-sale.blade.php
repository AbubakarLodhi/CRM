<x-filament-panels::page>

    <style>
        .pos-wrap {
            display: grid;
            grid-template-columns: 1fr 360px;
            grid-template-rows: 1fr;
            gap: 18px;
            padding: 18px;
            background: #f5f6f8;
            height: calc(100vh - 130px);
            box-sizing: border-box;
        }

        .pos-left {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow: hidden;
            height: 100%;
            box-sizing: border-box;
        }

        .pos-search-bar input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            outline: none;
            box-sizing: border-box;
        }

        .pos-search-bar input:focus { border-color: #1D9E75; }

        .pos-categories {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            flex-shrink: 0;
        }

        .pos-categories::-webkit-scrollbar { height: 4px; }
        .pos-categories::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .pos-categories::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

        .cat-btn {
            border: none;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            transition: 0.2s;
            color: #374151;
        }

        .cat-btn.active, .cat-btn:hover { background: #1D9E75; color: white; }

        .pos-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            overflow-y: scroll;
            overflow-x: hidden;
            flex: 1;
            min-height: 0;
            padding-left: 6px;
            padding-right: 6px;
            padding-top: 4px;
            align-content: start;
        }

        .pos-products::-webkit-scrollbar { width: 6px; }
        .pos-products::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 6px; }
        .pos-products::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 6px; }
        .pos-products::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        .pos-prod-card {
            background: #fff;
            border-radius: 16px;
            padding: 14px;
            border: 1px solid #f1f1f1;
            transition: 0.25s ease;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            position: relative;
        }

        .pos-prod-card:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 24px rgba(29,158,117,0.18);
            border-color: #1D9E75;
        }

        /* ── In-card quantity overlay ── */
        .pos-card-qty-ctrl {
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 3px;
            background: #1D9E75;
            border-radius: 10px;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(29,158,117,0.45);
            white-space: nowrap;
        }

        .pos-prod-card:hover .pos-card-qty-ctrl {
            transform: translateX(-50%) translateY(0);
        }

        .pos-card-qty-btn {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,0.55);
            background: rgba(255,255,255,0.18);
            color: #fff;
            font-size: 15px;
            line-height: 1;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
            padding: 0;
        }

        .pos-card-qty-btn:hover {
            background: rgba(255,255,255,0.35);
        }

        .pos-card-qty-val {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            min-width: 16px;
            text-align: center;
        }

        .pos-prod-icon {
            width: 100%;
            height: 100px;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .pos-prod-name { font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 4px; line-height: 1.3; }
        .pos-prod-sku { font-size: 11px; color: #9ca3af; }
        .pos-prod-price { font-size: 12px; color: #1D9E75; font-weight: 600; margin-top: 6px; }

        .pos-right {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
            box-sizing: border-box;
        }

        .pos-bill-header {
            padding: 7px 12px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .pos-bill-title { font-size: 13px; font-weight: 700; color: #111827; }

        /* Combined customer + discount row */
        .pos-top-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .pos-top-bar select {
            flex: 1;
            min-width: 0;
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 11px;
            background: #fff;
            color: #111827;
        }

        .pos-disc-group {
            display: flex;
            align-items: center;
            gap: 3px;
            flex-shrink: 0;
        }

        .pos-disc-group > span {
            font-size: 9px;
            color: #9ca3af;
            white-space: nowrap;
        }

        .pos-dtoggle-btn {
            padding: 2px 7px;
            border-radius: 5px;
            font-size: 10px;
            border: 1px solid #d1d5db;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            white-space: nowrap;
        }

        .pos-dtoggle-btn.active { background: #1D9E75; color: #fff; border-color: #1D9E75; }

        .pos-cart-header {
            display: grid;
            grid-template-columns: 2fr 80px 70px 28px;
            gap: 4px;
            padding: 4px 10px;
            background: #f9fafb;
            font-size: 10px;
            color: #6b7280;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .pos-cart-body { flex: 1; overflow-y: scroll; overflow-x: hidden; min-height: 0; }

        .pos-cart-body::-webkit-scrollbar { width: 4px; }
        .pos-cart-body::-webkit-scrollbar-track { background: #f9fafb; }
        .pos-cart-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .pos-cart-body::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        .pos-cart-item {
            display: grid;
            grid-template-columns: 2fr 80px 70px 28px;
            gap: 4px;
            align-items: center;
            padding: 6px 10px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 11px;
        }

        .pos-cart-item-name { font-weight: 600; color: #111827; font-size: 11px; line-height: 1.3; }
        .pos-cart-item-meta { font-size: 10px; color: #9ca3af; margin-top: 1px; }
        .pos-qty-ctrl { display: flex; align-items: center; gap: 3px; }

        .pos-qty-btn {
            width: 16px; height: 16px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background: #fff;
            font-size: 11px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #374151;
        }

        .pos-qty-btn:hover { background: #f3f4f6; }
        .pos-qty-val { font-size: 11px; min-width: 14px; text-align: center; color: #111827; }
        .pos-item-price { font-size: 11px; color: #1D9E75; font-weight: 700; text-align: right; }

        .pos-del-btn {
            width: 18px; height: 18px;
            border: none; background: transparent;
            color: #ef4444; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }

        .pos-item-inputs { grid-column: 1 / -1; display: flex; gap: 5px; padding: 2px 0 4px 0; }
        .pos-item-inputs label { font-size: 9px; color: #6b7280; display: block; margin-bottom: 1px; }

        .pos-item-inputs input {
            width: 58px;
            padding: 2px 4px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 10px;
            color: #111827;
        }

        .pos-empty { padding: 20px 12px; text-align: center; color: #9ca3af; font-size: 11px; }

        .pos-error {
            margin: 6px 12px;
            padding: 6px 10px;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            font-size: 11px;
            color: #b91c1c;
            flex-shrink: 0;
        }

        .pos-summary { padding: 5px 10px; background: #fafafa; border-top: 1px solid #e5e7eb; font-size: 10px; flex-shrink: 0; }

        .pos-sum-row { display: flex; justify-content: space-between; padding: 1px 0; color: #6b7280; }

        .pos-sum-row.total {
            font-weight: 700; font-size: 12px; color: #111827;
            padding-top: 5px; border-top: 1.5px solid #e5e7eb; margin-top: 3px;
        }

        .pos-payment-method { padding: 6px 10px; border-top: 1px solid #e5e7eb; flex-shrink: 0; }

        .pos-actions { display: flex; gap: 6px; padding: 8px 10px; border-top: 1px solid #e5e7eb; flex-shrink: 0; }

        .pos-cancel-btn {
            flex: 1; padding: 7px; border-radius: 7px;
            background: transparent; border: 1px solid #ef4444;
            color: #ef4444; font-size: 11px; font-weight: 500; cursor: pointer;
        }

        .pos-place-btn {
            flex: 2; padding: 7px; border-radius: 7px;
            background: #1D9E75; border: none;
            color: #fff; font-size: 11px; font-weight: 700; cursor: pointer;
        }

        .pos-place-btn:hover { background: #178a65; }

        .pos-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        }

        .pos-modal {
            background: #fff; border-radius: 16px; padding: 24px;
            width: 420px; max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .pos-modal h3 { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 16px; }
        .pos-modal label { font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px; margin-top: 10px; }

        .pos-modal select {
            width: 100%; padding: 8px 10px;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 13px; background: #fff; color: #111827;
        }

        .pos-modal-actions { display: flex; gap: 8px; margin-top: 18px; }

        .pos-modal-cancel {
            flex: 1; padding: 9px; border: 1px solid #d1d5db;
            border-radius: 8px; background: transparent;
            font-size: 13px; cursor: pointer; color: #374151;
        }

        .pos-modal-add {
            flex: 2; padding: 9px; border: none; border-radius: 8px;
            background: #1D9E75; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;
        }

        .pos-no-products { grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 40px; font-size: 13px; }

        /* Post-order modal */
        .pos-order-modal {
            background: #fff;
            border-radius: 20px;
            padding: 28px 24px 24px;
            width: 360px;
            max-width: 95vw;
            box-shadow: 0 24px 64px rgba(0,0,0,0.22);
            text-align: center;
        }

        .pos-order-modal .success-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            box-shadow: 0 4px 16px rgba(29,158,117,0.2);
        }

        .pos-order-modal h3 {
            font-size: 17px; font-weight: 700; color: #111827; margin-bottom: 4px;
        }

        .pos-order-modal .sale-no {
            font-size: 12px; color: #9ca3af; margin-bottom: 22px;
            font-weight: 500;
        }

        .pos-order-modal-btns {
            display: flex; gap: 10px; margin-bottom: 10px;
        }

        .pos-order-btn-invoice {
            flex: 1; padding: 11px 8px; border-radius: 10px;
            background: #1D9E75; color: #fff; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 5px;
            transition: background 0.15s;
        }

        .pos-order-btn-invoice:hover { background: #178a65; }

        .pos-order-btn-sales {
            flex: 1; padding: 11px 8px; border-radius: 10px;
            background: #f9fafb; color: #374151;
            border: 1px solid #e5e7eb; font-size: 13px;
            font-weight: 500; cursor: pointer;
            transition: background 0.15s;
        }

        .pos-order-btn-sales:hover { background: #f3f4f6; }

        .pos-order-btn-another {
            width: 100%; padding: 10px; border-radius: 10px;
            background: transparent; color: #6b7280;
            border: 1px solid #e5e7eb; font-size: 12px;
            font-weight: 500; cursor: pointer;
            transition: background 0.15s;
        }

        .pos-order-btn-another:hover { background: #f9fafb; color: #374151; }
    </style>

    <div
        x-data="{
            showModal: false,
            showOrderModal: false,
            showPaymentModal: false,
            lastSaleId: null,
            lastSaleNo: null,
            modalProduct: null,
            modalVariant: null,
            modalBranch: null,
            products: [],
            variants: [],
            branches: [],
            categories: [],
            search: '',
            activeCategory: '',
            totalAmount: 0,
            currentPayment: 0,

            init() {
                this.loadCategories();
                this.loadProducts();
                window.addEventListener('pos-order-placed', (e) => {
                    this.lastSaleId = e.detail.saleId;
                    this.lastSaleNo = e.detail.saleNo;
                    this.showOrderModal = true;
                });
            },

            loadCategories() {
                fetch('/pos/categories')
                    .then(r => r.json())
                    .then(d => { this.categories = d; });
            },

            loadProducts() {
                let url = '/pos/products?search=' + encodeURIComponent(this.search);
                if (this.activeCategory) url += '&category_id=' + this.activeCategory;
                fetch(url)
                    .then(r => r.json())
                    .then(d => this.products = d);
            },

            filterByCategory(categoryId) {
                this.activeCategory = categoryId;
                this.loadProducts();
            },

            loadVariants(productId) {
                this.modalVariant = null;
                this.modalBranch  = null;
                this.variants = [];
                this.branches = [];
                if (!productId) return;
                fetch('/pos/variants?product_id=' + productId)
                    .then(r => r.json())
                    .then(d => { this.variants = d; });
                fetch('/pos/branches?product_id=' + productId)
                    .then(r => r.json())
                    .then(d => { this.branches = d; });
            },

            openModal(product) {
                this.modalProduct = product;
                this.modalVariant = null;
                this.modalBranch  = null;
                this.variants = [];
                this.branches = [];
                this.loadVariants(product.id);
                this.showModal = true;
            },

            confirmAdd() {
                if (!this.modalVariant || !this.modalBranch) return;
                $wire.posAddItem(this.modalProduct.id, this.modalVariant, this.modalBranch);
                this.showModal = false;
            },

            openPaymentModal() {
                // Calculate total from posCart
                let cart = $wire.posCart || {};
                let total = Object.values(cart).reduce((sum, item) => {
                    return sum + parseFloat(item.line_total || 0);
                }, 0);
                this.totalAmount = Math.round(total * 100) / 100;
                this.currentPayment = this.totalAmount;
                this.showPaymentModal = true;
            },

            get amountDue() {
                return Math.max(0, Math.round((this.totalAmount - this.currentPayment) * 100) / 100);
            },

            confirmPaymentAndPlace() {
                $wire.set('posPaidAmount', this.currentPayment);
                $wire.set('posPaymentMethod', this.currentPayment >= this.totalAmount ? 'cash' : 'credit');
                this.showPaymentModal = false;
                $wire.posSubmit();
            },

            goToSales() {
                window.location.href = '/staff/sales';
            },

            openInvoice() {
                if (this.lastSaleId) {
                    window.open('/invoices/sale/' + this.lastSaleId, '_blank');
                }
            },

            /* ── Card quantity helpers ── */
            getProductQty(productId) {
                let total = 0;
                let cart = this.$wire.posCart || {};
                for (let k in cart) {
                    if (String(k).startsWith(String(productId) + '_')) {
                        total += parseInt(cart[k]?.quantity || 0);
                    }
                }
                return total;
            },
            getFirstCartKey(productId) {
                let cart = this.$wire.posCart || {};
                for (let k in cart) {
                    if (String(k).startsWith(String(productId) + '_')) return k;
                }
                return null;
            },
            cardIncrement(productId) {
                let k = this.getFirstCartKey(productId);
                if (k) this.$wire.posUpdateQty(k, 1);
            },
            cardDecrement(productId) {
                let k = this.getFirstCartKey(productId);
                if (k) this.$wire.posUpdateQty(k, -1);
            }
        }"
        x-init="init()"
    >

        <div class="pos-wrap">

            {{-- LEFT: Product grid --}}
            <div class="pos-left">
                <div class="pos-search-bar">
                    <input type="text" placeholder="Search products by name or SKU..."
                        x-model="search" @input.debounce.300ms="loadProducts()" />
                </div>

                <div class="pos-categories">
                    <button class="cat-btn" :class="activeCategory === '' ? 'active' : ''"
                        @click="filterByCategory('')">All</button>
                    <template x-for="cat in categories" :key="cat.id">
                        <button class="cat-btn"
                            :class="activeCategory === cat.id ? 'active' : ''"
                            @click="filterByCategory(cat.id)"
                            x-text="cat.name">
                        </button>
                    </template>
                </div>

                <div class="pos-products">
                    <template x-for="product in products" :key="product.id">
                        <div class="pos-prod-card" @click="openModal(product)">
                            <div class="pos-prod-icon">
                                <template x-if="product.image">
                                    <img :src="product.image" style="width:100%;height:100%;object-fit:cover;border-radius:12px;" />
                                </template>
                                <template x-if="!product.image">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="#9ca3af" style="width:36px;height:36px">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                    </svg>
                                </template>
                            </div>
                            <div class="pos-prod-name" x-text="product.name"></div>
                            <div class="pos-prod-sku" x-text="'SKU: ' + product.sku"></div>
                            <div class="pos-prod-price" x-text="'PKR ' + parseFloat(product.selling_price || 0).toLocaleString()"></div>

                            {{-- Quantity overlay: shown when this product has cart entries --}}
                            <div class="pos-card-qty-ctrl"
                                x-show="getProductQty(product.id) > 0"
                                @click.stop>
                                <button class="pos-card-qty-btn"
                                    @click.stop="cardDecrement(product.id)"
                                    title="Remove one">−</button>
                                <span class="pos-card-qty-val"
                                    x-text="getProductQty(product.id)"></span>
                                <button class="pos-card-qty-btn"
                                    @click.stop="cardIncrement(product.id)"
                                    title="Add one">+</button>
                            </div>
                        </div>
                    </template>
                    <template x-if="products.length === 0">
                        <div class="pos-no-products">No products found</div>
                    </template>
                </div>
            </div>

            {{-- RIGHT: Billing --}}
            <div class="pos-right">

                <div class="pos-bill-header">
                    <span class="pos-bill-title">🧾 Billing</span>
                    <span style="font-size:11px;color:#9ca3af">{{ $this->posSaleNo }}</span>
                </div>

                <div class="pos-top-bar">
                    <select wire:model.live="posCustomerId">
                        <option value="">Select customer...</option>
                        @foreach(\App\Models\Customer::query()
                            ->withoutTrashed()
                            ->where('merchant_id', auth()->user()?->merchant_id ?? auth()->user()?->id)
                            ->orderBy('name')->limit(200)->get(['id','name']) as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <div class="pos-disc-group">
                        <span>Disc:</span>
                        <button class="pos-dtoggle-btn {{ $this->posDiscountMode === 'percent' ? 'active' : '' }}"
                            wire:click="$set('posDiscountMode', 'percent')">%</button>
                        <button class="pos-dtoggle-btn {{ $this->posDiscountMode === 'amount' ? 'active' : '' }}"
                            wire:click="$set('posDiscountMode', 'amount')">PKR</button>
                    </div>
                </div>

                <div class="pos-cart-header">
                    <span>Item</span><span>QTY</span>
                    <span style="text-align:right">Price</span><span></span>
                </div>

                <div class="pos-cart-body">
                    @if(count($this->posCart) === 0)
                        <div class="pos-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="#d1d5db" style="width:36px;height:36px;margin:0 auto 8px;display:block">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                            </svg>
                            <div>No items added yet.<br>Click a product to add it.</div>
                        </div>
                    @else
                        @foreach($this->posCart as $key => $item)
                            <div class="pos-cart-item">
                                <div>
                                    <div class="pos-cart-item-name">{{ $item['product_name'] }}</div>
                                    <div class="pos-cart-item-meta">{{ $item['variant_name'] }} · {{ $item['branch_name'] }}</div>
                                </div>
                                <div class="pos-qty-ctrl">
                                    <button class="pos-qty-btn" wire:click="posUpdateQty('{{ $key }}', -1)">−</button>
                                    <span class="pos-qty-val">{{ $item['quantity'] }}</span>
                                    <button class="pos-qty-btn" wire:click="posUpdateQty('{{ $key }}', 1)">+</button>
                                </div>
                                <div class="pos-item-price">PKR {{ number_format($item['line_total'], 2) }}</div>
                                <button class="pos-del-btn" wire:click="posRemoveItem('{{ $key }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                                <div class="pos-item-inputs">
                                    @if($this->posDiscountMode === 'percent')
                                        <div>
                                            <label>Disc %</label>
                                            <input type="number" min="0" max="100" step="0.01"
                                                value="{{ $item['discount'] }}"
                                                wire:change="posUpdateField('{{ $key }}', 'discount', $event.target.value)" />
                                        </div>
                                        <div>
                                            <label>Tax %</label>
                                            <input type="number" min="0" max="100" step="0.01"
                                                value="{{ $item['tax'] }}"
                                                wire:change="posUpdateField('{{ $key }}', 'tax', $event.target.value)" />
                                        </div>
                                    @else
                                        <div>
                                            <label>Disc PKR</label>
                                            <input type="number" min="0" step="0.01"
                                                value="{{ $item['discount_amount'] }}"
                                                wire:change="posUpdateField('{{ $key }}', 'discount_amount', $event.target.value)" />
                                        </div>
                                        <div>
                                            <label>Tax PKR</label>
                                            <input type="number" min="0" step="0.01"
                                                value="{{ $item['tax_amount'] }}"
                                                wire:change="posUpdateField('{{ $key }}', 'tax_amount', $event.target.value)" />
                                        </div>
                                    @endif
                                    <div>
                                        <label>Unit Price</label>
                                        <input type="number" min="0" step="0.01"
                                            value="{{ $item['unit_price'] }}"
                                            wire:change="posUpdateField('{{ $key }}', 'unit_price', $event.target.value)" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                @error('pos')
                    <div class="pos-error">{{ $message }}</div>
                @enderror

                <div class="pos-summary">
                    <div class="pos-sum-row"><span>Subtotal</span><span>PKR {{ number_format($this->getPosSubtotal(), 2) }}</span></div>
                    <div class="pos-sum-row"><span>Discount</span><span>PKR {{ number_format($this->getPosDiscount(), 2) }}</span></div>
                    <div class="pos-sum-row"><span>Tax</span><span>PKR {{ number_format($this->getPosTax(), 2) }}</span></div>
                    <div class="pos-sum-row total"><span>Total</span><span>PKR {{ number_format($this->getPosTotal(), 2) }}</span></div>
                </div>

                <div class="pos-actions">
                    <button class="pos-cancel-btn" wire:click="posClearCart">Clear</button>
                    <button class="pos-place-btn" @click="openPaymentModal()">Place Order</button>
                </div>

            </div>
        </div>

        {{-- Add item modal --}}
        <template x-if="showModal">
            <div class="pos-modal-overlay" @click.self="showModal = false">
                <div class="pos-modal">
                    <h3 x-text="'Add: ' + (modalProduct?.name ?? '')"></h3>
                    <label>Branch</label>
                    <select x-model="modalBranch">
                        <option value="">Select branch...</option>
                        <template x-for="b in branches" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                    <label>Product Variant</label>
                    <select x-model="modalVariant">
                        <option value="">Select variant...</option>
                        <template x-for="v in variants" :key="v.id">
                            <option :value="v.id" x-text="(v.name || v.sku || v.id) + ' — PKR ' + parseFloat(v.selling_price || 0).toFixed(2)"></option>
                        </template>
                    </select>
                    <div class="pos-modal-actions">
                        <button class="pos-modal-cancel" @click="showModal = false">Cancel</button>
                        <button class="pos-modal-add" @click="confirmAdd()"
                            :disabled="!modalVariant || !modalBranch"
                            :style="(!modalVariant || !modalBranch) ? 'opacity:0.5;cursor:not-allowed' : ''">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Payment modal --}}
        <template x-if="showPaymentModal">
            <div class="pos-modal-overlay">
                <div class="pos-modal" style="width:360px;">
                    <h3 style="font-size:16px;font-weight:700;color:#111827;margin-bottom:16px;">💳 Payment</h3>

                    <div style="background:#f9fafb;border-radius:10px;padding:14px;margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                            <span style="color:#6b7280;">Total Amount</span>
                            <span style="font-weight:700;color:#111827;" x-text="'PKR ' + totalAmount.toLocaleString('en-PK', {minimumFractionDigits:2})"></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;">
                            <span style="color:#6b7280;">Amount Due</span>
                            <span style="font-weight:700;color:#ef4444;" x-text="'PKR ' + amountDue.toLocaleString('en-PK', {minimumFractionDigits:2})"></span>
                        </div>
                    </div>

                    <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:6px;">Current Payment (PKR)</label>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        :max="totalAmount"
                        x-model.number="currentPayment"
                        placeholder="Enter amount paid..."
                        style="width:100%;padding:10px 12px;border:2px solid #1D9E75;border-radius:8px;font-size:14px;color:#111827;box-sizing:border-box;margin-bottom:10px;outline:none;"
                    />

                    <div style="display:flex;gap:8px;margin-bottom:16px;">
                        <button @click="currentPayment = totalAmount"
                            style="flex:1;padding:6px;border-radius:6px;border:1px solid #1D9E75;color:#1D9E75;background:#f0fdf9;font-size:11px;font-weight:600;cursor:pointer;">
                            ✓ Full Payment
                        </button>
                        <button @click="currentPayment = 0"
                            style="flex:1;padding:6px;border-radius:6px;border:1px solid #d1d5db;color:#6b7280;background:#fff;font-size:11px;cursor:pointer;">
                            No Payment
                        </button>
                    </div>

                    <div style="display:flex;gap:8px;">
                        <button @click="showPaymentModal = false"
                            style="flex:1;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:13px;cursor:pointer;color:#374151;">
                            Cancel
                        </button>
                        <button @click="confirmPaymentAndPlace()"
                            style="flex:2;padding:10px;border:none;border-radius:8px;background:#1D9E75;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                            ✓ OK & Place Order
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Post-order modal --}}
        <template x-if="showOrderModal">
            <div class="pos-modal-overlay">
                <div class="pos-order-modal">
                    <div class="success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="2.5" stroke="#1D9E75" style="width:28px;height:28px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <h3>Order Placed!</h3>
                    <p class="sale-no" x-text="'Sale #' + (lastSaleNo ?? '') + ' created successfully'"></p>

                    <div class="pos-order-modal-btns">
                        <button class="pos-order-btn-invoice" @click="openInvoice()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            View Invoice
                        </button>
                        <button class="pos-order-btn-sales" @click="goToSales()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" style="width:15px;height:15px;display:inline;margin-right:3px">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            Go to Sales
                        </button>
                    </div>

                    <button class="pos-order-btn-another" @click="showOrderModal = false">
                        + Place Another Order
                    </button>
                </div>
            </div>
        </template>

    </div>

</x-filament-panels::page>