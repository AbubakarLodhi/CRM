<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Resources\Activities\Support\ActivityPerformer;
use App\Models\Attachment;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vendor;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityInfolist
{
    /** @var array<string, string> */
    protected static array $resolvedValueCache = [];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Information')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('event')
                            ->badge()
                            ->color(fn (?string $state) => match (strtolower((string) $state)) {
                                'created'  => 'success',
                                'updated'  => 'info',
                                'deleted'  => 'danger',
                                'restored' => 'warning',
                                default    => 'gray',
                            }),

                        TextEntry::make('performed_by')
                            ->label('Performed By')
                            ->getStateUsing(fn ($record) => ActivityPerformer::resolve($record)),

                        TextEntry::make('actor_type')
                            ->label('Actor Type')
                            ->getStateUsing(fn ($record) => $record->user_type
                                ? class_basename((string) $record->user_type)
                                : 'System'
                            ),

                        TextEntry::make('auditable_type')
                            ->label('Entity Type')
                            ->formatStateUsing(fn (?string $state): string => $state
                                ? Str::headline(class_basename($state))
                                : '-'
                            ),

                        TextEntry::make('auditable_id')
                            ->label('Entity')
                            ->getStateUsing(fn ($record) => self::resolveAuditableEntityValue($record))
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Date & Time')
                            ->dateTime('d/m/Y H:i:s'),

                        TextEntry::make('url')
                            ->label('URL')
                            ->columnSpanFull()
                            ->placeholder('-'),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('-'),

                        TextEntry::make('tags')
                            ->label('Tags')
                            ->placeholder('-'),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Changes')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('changes_diff')
                            ->label('')
                            ->columnSpanFull()
                            ->html()
                            ->getStateUsing(fn ($record) => self::renderChangesDiff($record)),
                    ]),
            ]);
    }

    // ─── Changes Diff ────────────────────────────────────────────────────────

    protected static function renderChangesDiff(object $record): string
    {
        $event   = strtolower((string) ($record->event ?? ''));
        $rawNew  = self::normalizeToArray($record->new_values ?? null);
        $rawOld  = self::normalizeToArray($record->old_values ?? null);

        $processedNew = self::processValuesForDiff($rawNew, $rawOld);
        $processedOld = self::processValuesForDiff($rawOld, []);

        if (empty($processedNew) && empty($processedOld)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400 italic py-1">No recorded changes.</p>';
        }

        return match ($event) {
            'created', 'restored' => self::buildSingleColumnTable($processedNew),
            'deleted'              => self::buildSingleColumnTable($processedOld),
            'updated'              => self::buildDiffTable($processedOld, $processedNew),
            default                => self::buildSingleColumnTable($processedNew ?: $processedOld),
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $fullRow  Extra context (e.g. attachable_type)
     * @return array<string, string>
     */
    protected static function processValuesForDiff(array $values, array $fullRow): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            if (is_array($value)) {
                $result[self::humanizeKey($key)] = json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
                continue;
            }

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $context = array_merge($values, $fullRow);
            $resolved = self::resolveKnownIdKey($key, $value, $context);

            if ($resolved !== null) {
                $result[self::humanizeKey($key)] = $resolved;
                continue;
            }

            if (self::isLikelyIdKey($key)) {
                // Show a short UUID hint instead of hiding the field
                $result[self::humanizeKey($key)] = substr($value, 0, 8) . '…';
                continue;
            }

            $sanitized = self::sanitizeLooseValueString($value);

            if ($sanitized !== '') {
                $result[self::humanizeKey($key)] = $sanitized;
            }
        }

        return $result;
    }

    /** @param  array<string, string>  $values */
    protected static function buildDiffTable(array $old, array $new): string
    {
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        if (empty($allKeys)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400 italic py-1">No recorded changes.</p>';
        }

        $rows = '';

        foreach ($allKeys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            $oldDisplay = $oldVal !== null
                ? htmlspecialchars((string) $oldVal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : '<span class="text-gray-400 dark:text-gray-500">—</span>';

            $newDisplay = $newVal !== null
                ? htmlspecialchars((string) $newVal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : '<span class="text-gray-400 dark:text-gray-500">—</span>';

            $changedClass = $oldVal !== $newVal
                ? 'bg-amber-50 dark:bg-amber-900/10'
                : '';

            $keyDisplay = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $rows .= "<tr class=\"border-b border-gray-100 dark:border-gray-700 {$changedClass}\">"
                . "<td class=\"py-2 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap\">{$keyDisplay}</td>"
                . "<td class=\"py-2 px-4 text-sm text-red-600 dark:text-red-400 break-all\">{$oldDisplay}</td>"
                . "<td class=\"py-2 px-4 text-sm text-green-700 dark:text-green-400 break-all\">{$newDisplay}</td>"
                . '</tr>';
        }

        $header = '<thead>'
            . '<tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-600">'
            . '<th class="py-2 px-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Field</th>'
            . '<th class="py-2 px-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Previous Value</th>'
            . '<th class="py-2 px-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">New Value</th>'
            . '</tr>'
            . '</thead>';

        return '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">'
            . "<table class=\"w-full\">{$header}<tbody>{$rows}</tbody></table>"
            . '</div>';
    }

    /** @param  array<string, string>  $values */
    protected static function buildSingleColumnTable(array $values): string
    {
        if (empty($values)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400 italic py-1">No recorded values.</p>';
        }

        $rows = '';

        foreach ($values as $key => $value) {
            $keyDisplay = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $valDisplay = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $rows .= '<tr class="border-b border-gray-100 dark:border-gray-700">'
                . "<td class=\"py-2 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 w-1/3 whitespace-nowrap\">{$keyDisplay}</td>"
                . "<td class=\"py-2 px-4 text-sm text-gray-900 dark:text-gray-100 break-all\">{$valDisplay}</td>"
                . '</tr>';
        }

        $header = '<thead>'
            . '<tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-gray-600">'
            . '<th class="py-2 px-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Field</th>'
            . '<th class="py-2 px-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Value</th>'
            . '</tr>'
            . '</thead>';

        return '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">'
            . "<table class=\"w-full\">{$header}<tbody>{$rows}</tbody></table>"
            . '</div>';
    }

    // ─── Entity Resolution ───────────────────────────────────────────────────

    public static function resolveAuditableEntityValue(object $record): string
    {
        $modelClass = (string) ($record->auditable_type ?? '');
        $id         = (string) ($record->auditable_id ?? '');

        if ($modelClass === '' || $id === '') {
            return '-';
        }

        $displayColumns = match ($modelClass) {
            Attachment::class                                                          => ['photo_url', 'meta_type', 'type'],
            Merchant::class, Business::class, Branch::class, Customer::class,
            Vendor::class, Category::class, Brand::class, BrandModel::class,
            Country::class, City::class, User::class                                  => ['name', 'email'],
            Product::class, ProductVariant::class                                     => ['name', 'sku'],
            Sale::class                                                                => ['sale_no'],
            Purchase::class                                                            => ['purchase_no'],
            default                                                                    => ['name', 'title', 'code', 'number', 'sale_no', 'purchase_no', 'sku', 'photo_url'],
        };

        return self::resolveModelDisplayValue($modelClass, $id, $displayColumns) ?: '-';
    }

    // ─── FK Resolution ───────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $fullRow
     */
    protected static function resolveKnownIdKey(string $key, string $value, array $fullRow): ?string
    {
        $modelMap = [
            'merchant_id'        => [Merchant::class, ['name']],
            'business_id'        => [Business::class, ['name']],
            'branch_id'          => [Branch::class, ['name']],
            'customer_id'        => [Customer::class, ['name']],
            'vendor_id'          => [Vendor::class, ['name']],
            'product_id'         => [Product::class, ['name', 'sku']],
            'product_variant_id' => [ProductVariant::class, ['sku', 'name']],
            'category_id'        => [Category::class, ['name']],
            'brand_id'           => [Brand::class, ['name']],
            'brand_model_id'     => [BrandModel::class, ['name']],
            'country_id'         => [Country::class, ['name']],
            'city_id'            => [City::class, ['name']],
            'created_by'         => [User::class, ['name', 'email']],
            'updated_by'         => [User::class, ['name', 'email']],
            'deleted_by'         => [User::class, ['name', 'email']],
            'user_id'            => [User::class, ['name', 'email']],
            'sale_id'            => [Sale::class, ['sale_no']],
            'purchase_id'        => [Purchase::class, ['purchase_no']],
            'attachable_id'      => [null, []],
        ];

        if (! array_key_exists($key, $modelMap)) {
            return null;
        }

        if ($key === 'attachable_id') {
            $attachableType = $fullRow['attachable_type'] ?? null;

            if (! is_string($attachableType) || ! class_exists($attachableType)) {
                return null;
            }

            return self::resolveModelDisplayValue($attachableType, $value, ['name', 'title', 'sale_no', 'purchase_no', 'sku']);
        }

        [$modelClass, $displayColumns] = $modelMap[$key];

        if (! is_string($modelClass) || $modelClass === '') {
            return null;
        }

        return self::resolveModelDisplayValue($modelClass, $value, $displayColumns);
    }

    /**
     * @param  array<int, string>  $displayColumns
     */
    protected static function resolveModelDisplayValue(string $modelClass, string $id, array $displayColumns): ?string
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $cacheKey = $modelClass . '|' . $id . '|' . implode(',', $displayColumns);

        if (array_key_exists($cacheKey, self::$resolvedValueCache)) {
            return self::$resolvedValueCache[$cacheKey];
        }

        /** @var Model|null $model */
        $model = $modelClass::query()->find($id);

        if (! $model) {
            self::$resolvedValueCache[$cacheKey] = '';

            return null;
        }

        foreach ($displayColumns as $column) {
            $candidate = data_get($model, $column);

            if (is_string($candidate) && trim($candidate) !== '') {
                self::$resolvedValueCache[$cacheKey] = $candidate;

                return $candidate;
            }
        }

        self::$resolvedValueCache[$cacheKey] = '';

        return null;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Converts snake_case / camelCase keys to readable labels, stripping _id suffix. */
    protected static function humanizeKey(string $key): string
    {
        $key = preg_replace('/_id$/', '', $key) ?? $key;

        return Str::headline($key);
    }

    protected static function isLikelyIdKey(string $key): bool
    {
        return $key === 'id' || Str::endsWith($key, '_id');
    }

    /** @return array<string, mixed> */
    protected static function normalizeToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected static function sanitizeLooseValueString(string $value): string
    {
        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];

        $filtered = array_values(array_filter($parts, function (string $part): bool {
            $token = trim($part);

            if ($token === '' || $token === '{}' || $token === '[]') {
                return false;
            }

            return ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $token);
        }));

        return implode(', ', $filtered);
    }
}
