<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Utils\Traits;

use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait WithSecureContext
{
    public const CONTEXT_UPDATE = 'secureContext.updated';

    public const CONTEXT_READY = 'flow2.context.ready';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getContext(?string $key): mixed
    {
        $context = Cache::get($key) ?? [];

        return $context;
    }

    public function setContext(string $key, string $property, $value): array
    {
        $clone = $this->getContext($key);

        data_set($clone, $property, $value);

        Cache::put($key, $clone, now()->addHour());

        $this->dispatch(self::CONTEXT_UPDATE);

        return $clone;
    }

    public function bulkSetContext(string $key, array $data): array
    {

        $clone = $this->getContext($key);
        $clone = array_merge($clone, $data);

        Cache::put($key, $clone, now()->addHour());

        $this->dispatch(self::CONTEXT_UPDATE);

        return $clone;

    }

    // public function resetContext(): void
    // {
    //     \Illuminate\Support\Facades\Cache::forget(session()->getId());
    // }
}
