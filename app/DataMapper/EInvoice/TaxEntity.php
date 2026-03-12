<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\DataMapper\EInvoice;

class TaxEntity
{
    public string $version = 'alpha';

    public ?int $legal_entity_id = null;

    public string $company_key = '';

    /** @var array<string> */
    public array $received_documents = [];

    public bool $acts_as_sender = true;

    public bool $acts_as_receiver = true;

    /**
     * __construct
     */
    public function __construct(mixed $entity = null)
    {
        if (!$entity) {
            $this->init();

            return;
        }

        $entityArray = is_object($entity) ? get_object_vars($entity) : $entity;

        foreach ($entityArray as $key => $value) {
            $this->{$key} = $value;
        }

        $this->migrate();
    }

    public function init(): self
    {
        return $this;
    }

    private function migrate(): self
    {
        return $this;
    }
}
