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
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                'restored' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('performed_by')
                            ->label('Performed By')
                            ->getStateUsing(fn ($record) => ActivityPerformer::resolve($record)),

                        TextEntry::make('actor_type')
                            ->label('Actor Type')
                            ->getStateUsing(fn ($record) => $record->user_type ? class_basename((string) $record->user_type) : 'System'),

                        TextEntry::make('auditable_type')
                            ->label('Entity')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),

                        TextEntry::make('auditable_id')
                            ->label('Entity ID')
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
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('old_values')
                            ->label('Old Values')
                            ->formatStateUsing(fn ($state) => self::toPrettyJson($state))
                            ->placeholder('{}'),

                        TextEntry::make('new_values')
                            ->label('New Values')
                            ->formatStateUsing(fn ($state) => self::toPrettyJson($state))
                            ->placeholder('{}'),
                    ]),
            ]);
    }

    protected static function toPrettyJson(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '{}';
        }

        $decoded = is_array($state) ? $state : json_decode((string) $state, true);

        if (! is_array($decoded)) {
            $sanitized = self::sanitizeLooseValueString((string) $state);

            return $sanitized !== '' ? $sanitized : '{}';
        }

        $decoded = self::replaceIdsWithReadableValues($decoded);

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function replaceIdsWithReadableValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::replaceIdsWithReadableValues($value);
                continue;
            }

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $resolved = self::resolveKnownIdKey($key, $value, $data);

            if ($resolved !== null) {
                $data[$key] = $resolved;
                continue;
            }

            if (self::isLikelyIdKey($key)) {
                unset($data[$key]);
                continue;
            }

            $sanitized = self::sanitizeLooseValueString($value);

            if ($sanitized === '') {
                unset($data[$key]);
                continue;
            }

            $data[$key] = $sanitized;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $fullRow
     */
    protected static function resolveKnownIdKey(string $key, string $value, array $fullRow): ?string
    {
        $modelMap = [
            'merchant_id' => [Merchant::class, ['name']],
            'business_id' => [Business::class, ['name']],
            'branch_id' => [Branch::class, ['name']],
            'customer_id' => [Customer::class, ['name']],
            'vendor_id' => [Vendor::class, ['name']],
            'product_id' => [Product::class, ['name', 'sku']],
            'product_variant_id' => [ProductVariant::class, ['sku', 'name']],
            'category_id' => [Category::class, ['name']],
            'brand_id' => [Brand::class, ['name']],
            'brand_model_id' => [BrandModel::class, ['name']],
            'country_id' => [Country::class, ['name']],
            'city_id' => [City::class, ['name']],
            'created_by' => [User::class, ['name', 'email']],
            'updated_by' => [User::class, ['name', 'email']],
            'deleted_by' => [User::class, ['name', 'email']],
            'user_id' => [User::class, ['name', 'email']],
            'sale_id' => [Sale::class, ['sale_no']],
            'purchase_id' => [Purchase::class, ['purchase_no']],
            'attachable_id' => [null, []],
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

        $cacheKey = $modelClass.'|'.$id.'|'.implode(',', $displayColumns);

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

    protected static function isLikelyIdKey(string $key): bool
    {
        return $key === 'id' || Str::endsWith($key, '_id');
    }

    protected static function resolveAuditableEntityValue(object $record): string
    {
        $modelClass = (string) ($record->auditable_type ?? '');
        $id = (string) ($record->auditable_id ?? '');

        if ($modelClass === '' || $id === '') {
            return '-';
        }

        $displayColumns = match ($modelClass) {
            Attachment::class => ['photo_url', 'meta_type', 'type'],
            Merchant::class, Business::class, Branch::class, Customer::class, Vendor::class,
            Category::class, Brand::class, BrandModel::class, Country::class, City::class,
            User::class => ['name', 'email'],
            Product::class, ProductVariant::class => ['name', 'sku'],
            Sale::class => ['sale_no'],
            Purchase::class => ['purchase_no'],
            default => ['name', 'title', 'code', 'number', 'sale_no', 'purchase_no', 'sku', 'photo_url'],
        };

        return self::resolveModelDisplayValue($modelClass, $id, $displayColumns) ?: '-';
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
