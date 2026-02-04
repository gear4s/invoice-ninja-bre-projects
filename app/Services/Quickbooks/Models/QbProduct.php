<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\Quickbooks\Models;

use Carbon\Carbon;
use App\Models\Product;
use App\DataMapper\ProductSync;
use App\Factory\ProductFactory;
use App\Interfaces\SyncInterface;
use App\Services\Quickbooks\QuickbooksService;
use App\Services\Quickbooks\Transformers\ProductTransformer;

class QbProduct implements SyncInterface
{
    protected ProductTransformer $product_transformer;

    public function __construct(public QuickbooksService $service)
    {

        $this->product_transformer = new ProductTransformer($service->company);

    }

    public function find(string $id): mixed
    {
        return $this->service->sdk->FindById('Item', $id);
    }

    public function syncToNinja(array $records): void
    {

        foreach ($records as $record) {

            $ninja_data = $this->product_transformer->qbToNinja($record);

            if ($product = $this->findProduct($ninja_data['id'])) {
                $product->fill($ninja_data);
                $product->save();
            }
        }

    }

    public function syncToForeign(array $records): void {}

    private function findProduct(string $key): ?Product
    {
        $search = Product::query()
                         ->withTrashed()
                         ->where('company_id', $this->service->company->id)
                         ->where('sync->qb_id', $key);

        if ($search->count() == 0) {

            $product = ProductFactory::create($this->service->company->id, $this->service->company->owner()->id);

            $sync = new ProductSync();
            $sync->qb_id = $key;
            $product->sync = $sync;

            return $product;

        } elseif ($search->count() == 1) {
            return $this->service->syncable('product', \App\Enum\SyncDirection::PULL) ? $search->first() : null;
        }

        return null;

    }

    public function sync(string $id, string $last_updated): void
    {
        $qb_record = $this->find($id);

        if ($this->service->syncable('product', \App\Enum\SyncDirection::PULL) && $ninja_record = $this->findProduct($id)) {

            if (Carbon::parse($last_updated) > Carbon::parse($ninja_record->updated_at)) {
                $ninja_data = $this->product_transformer->qbToNinja($qb_record);

                $ninja_record->fill($ninja_data);
                $ninja_record->save();

            }

        }

    }

    /**
    * findOrCreateProduct
    *
    * Finds or creates a product in quickbooks
    *
    * @param  object $line_item
    * @return string
    */
    public function findOrCreateProduct(object $line_item): string
    {

        $product = \App\Models\Product::where('company_id', $this->service->company->id)
                                          ->where('product_key', $line_item->product_key)
                                          ->first();

        if ($product && isset($product->sync->qb_id)) {
            return $product->sync->qb_id;
        }

        $item_name = strlen($line_item->product_key ?? '') > 0 ? $line_item->product_key : 'Product ' . uniqid();

        $escaped_name = str_replace("'", "''", $item_name);
        $query = "SELECT * FROM Item WHERE Name = '{$escaped_name}' AND Active = true MAXRESULTS 1";
        $existing_items = $this->service->sdk->Query($query);
        if (!empty($existing_items) && isset($existing_items[0])) {
            $existing_item = $existing_items[0];
            $existing_id = data_get($existing_item, 'Id') ?? data_get($existing_item, 'Id.value');
            if ($existing_id) {
                return $existing_id;
            }
        }

        return $this->createQbProduct($line_item);

    }

    /**
     * createQbProduct
     *
     * Creates a product in quickbooks
     *
     * @param  object $line_item
     * @return string
     */
    private function createQbProduct(object $line_item): string
    {

        $product_data = $this->product_transformer->qbTransform($line_item, $this->service->getIncomeAccountId());

        $qb_item = \QuickBooksOnline\API\Facades\Item::create($product_data);

        $result = $this->service->sdk->Add($qb_item);

        $qb_id = data_get($result, 'Id') ?? data_get($result, 'Id.value');

        return $qb_id;
    }
}
