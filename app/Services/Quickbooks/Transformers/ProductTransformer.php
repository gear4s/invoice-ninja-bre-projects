<?php

/**
 * Invoice Ninja (https://clientninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\Quickbooks\Transformers;

use App\Models\Product;

/**
 * Class ProductTransformer.
 */
class ProductTransformer extends BaseTransformer
{
    public function qbToNinja(mixed $qb_data)
    {
        return $this->transform($qb_data);
    }



    public function qbTransform($line_item, $income_account_id): array
    {
        return [
            'Name' => strlen($line_item->product_key ?? '') > 0 ? $line_item->product_key : 'Product ' . uniqid(),
            'Description' => $line_item->notes,
            'PurchaseCost' => $line_item->product_cost ?? 0,
            'UnitPrice' => $line_item->cost,
            'Type' => $line_item->type_id == '2' || in_array($line_item->tax_id, ['5','8']) ? 'Service' : 'NonInventory',
            'IncomeAccountRef' => [
                'value' => strlen($line_item->income_account_id ?? '') > 0 ? $line_item->income_account_id : $income_account_id,
            ],
        ];
    }

    public function transform(mixed $data): array
    {
        $tax_id = data_get($data, 'Taxable', '1') == 'true' ? '1' : '5';

        if($tax_id == '1' && data_get($data, 'Type') == 'Service') {
            $tax_id = '2';
        } 

        return [
            'id' => data_get($data, 'Id', null),
            'product_key' => data_get($data, 'Name', data_get($data, 'FullyQualifiedName', '')),
            'notes' => data_get($data, 'Description', ''),
            'cost' => data_get($data, 'PurchaseCost', 0) ?? 0,
            'price' => data_get($data, 'UnitPrice', 0) ?? 0,
            'in_stock_quantity' => data_get($data, 'QtyOnHand', 0) ?? 0,
            'income_account_id' => data_get($data, 'IncomeAccountRef.value') ?? data_get($data, 'IncomeAccountRef') ?? null,
            'type_id' => data_get($data, 'Type') == 'Service' ? '2' : '1',
            'tax_id' => $tax_id,
        ];

    }

}
