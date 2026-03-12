<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Livewire\BillingPortal\Cart;

use App\Models\Subscription;
use App\Utils\Ninja;
use App\Utils\Traits\MakesHash;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Cart extends Component
{
    use MakesHash;

    public array $context;

    public string $subscription_id;

    public function mount()
    {

        App::forgetInstance('translator');
        $t = app('translator');
        $t->replace(Ninja::transformTranslations($this->subscription()->company->settings));
        App::setLocale($this->subscription()->company->locale());

    }

    #[Computed()]
    public function subscription()
    {
        return Subscription::find($this->decodePrimaryKey($this->subscription_id))->withoutRelations()->makeHidden(['webhook_configuration', 'steps']);
    }

    public function handleSubmit()
    {
        $this->dispatch('purchase.next');
    }

    public function showOptionalProductsLabel()
    {
        $optional = [
            ...$this->context['bundle']['optional_recurring_products'] ?? [],
            ...$this->context['bundle']['optional_one_time_products'] ?? [],
        ];

        return count($optional) > 0;
    }

    public function payableAmount()
    {
        return isset($this->context['products']) && collect($this->context['products'])->sum('total_raw') > 0;
    }

    public function render()
    {
        return view('billing-portal.v3.cart.cart');
    }
}
