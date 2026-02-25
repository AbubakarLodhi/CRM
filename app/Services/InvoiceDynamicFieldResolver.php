<?php

namespace App\Services;

use App\Models\InvoiceDynamicField;
use App\Models\InvoiceDynamicGroup;
use App\Models\Purchase;
use App\Models\Sale;

class InvoiceDynamicFieldResolver
{
    protected static function getEntityValue(mixed $entity, ?string $key): mixed
    {
        $normalizedKey = trim((string) $key);

        if ($normalizedKey !== '') {
            $value = data_get($entity, $normalizedKey);

            if (filled($value)) {
                return $value;
            }
        }

        // Fallback for common entity types when key is missing/invalid.
        return data_get($entity, 'name');
    }

    /**
     * @param  Sale|Purchase  $record
     */
    public static function resolveValue(InvoiceDynamicField $field, Sale|Purchase $record): string
    {
        if ($field->value_type === 'static') {
            return trim((string) ($field->static_value ?? ''));
        }

        $value = match ($field->value_type) {
            'merchant' => self::getEntityValue($record->merchant, $field->value_key),
            'business' => self::getEntityValue($record->items->first()?->business, $field->value_key),
            'branch' => self::getEntityValue($record->items->first()?->branch, $field->value_key),
            'customer' => $record instanceof Sale ? self::getEntityValue($record->customer, $field->value_key) : null,
            'vendor' => $record instanceof Purchase ? self::getEntityValue($record->vendor, $field->value_key) : null,
            'sale' => $record instanceof Sale ? data_get($record, $field->value_key) : null,
            'purchase' => $record instanceof Purchase ? data_get($record, $field->value_key) : null,
            default => null,
        };

        if (is_array($value)) {
            return trim(implode(', ', array_filter($value, fn ($item) => filled($item))));
        }

        return trim((string) ($value ?? ''));
    }

    /**
     * @param  Sale|Purchase  $record
     * @return array{header: array<int, array{group_name: string, fields: array<int, array{label: string, value: string}>>>, footer: array<int, array{group_name: string, fields: array<int, array{label: string, value: string}>>}}
     */
    public static function resolveGroups(Sale|Purchase $record, string $headerGroupId, string $footerGroupId): array
    {
        $result = [
            'header' => [],
            'footer' => [],
        ];

        if ($headerGroupId !== '__default') {
            $headerGroup = InvoiceDynamicGroup::query()
                ->with('fields')
                ->where('merchant_id', $record->merchant_id)
                ->where('section', 'header')
                ->where('is_active', true)
                ->find($headerGroupId);

            if ($headerGroup) {
                $result['header'][] = [
                    'group_name' => trim((string) $headerGroup->name) ?: 'Details',
                    'fields' => self::resolveGroupFields($headerGroup->fields->all(), $record),
                ];
            }
        }

        if ($footerGroupId !== '__default') {
            $footerGroup = InvoiceDynamicGroup::query()
                ->with('fields')
                ->where('merchant_id', $record->merchant_id)
                ->where('section', 'footer')
                ->where('is_active', true)
                ->find($footerGroupId);

            if ($footerGroup) {
                $result['footer'][] = [
                    'group_name' => trim((string) $footerGroup->name) ?: 'Details',
                    'fields' => self::resolveGroupFields($footerGroup->fields->all(), $record),
                ];
            }
        }

        return [
            'header' => array_values(array_filter($result['header'], fn (array $group): bool => ! empty($group['fields']))),
            'footer' => array_values(array_filter($result['footer'], fn (array $group): bool => ! empty($group['fields']))),
        ];
    }

    /**
     * @param  array<int, InvoiceDynamicField>  $fields
     * @param  Sale|Purchase  $record
     * @return array<int, array{label: string, value: string}>
     */
    protected static function resolveGroupFields(array $fields, Sale|Purchase $record): array
    {
        $rows = [];

        foreach ($fields as $field) {
            if (! $field->is_active) {
                continue;
            }

            $value = self::resolveValue($field, $record);

            if ($value === '') {
                continue;
            }

            $rows[] = [
                'label' => $field->label,
                'value' => $value,
            ];
        }

        return $rows;
    }
}
