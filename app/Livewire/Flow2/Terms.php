<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Livewire\Flow2;

use App\Models\InvoiceInvitation;
use App\Utils\Traits\WithSecureContext;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Terms extends Component
{
    use WithSecureContext;

    public $variables;

    public $_key;

    public function mount()
    {
        $this->variables = $this->getContext($this->_key)['variables'];
    }

    #[Computed()]
    public function invoice()
    {
        $_context = $this->getContext($this->_key);

        $invitation_id = $_context['invitation_id'];

        $db = $_context['db'];

        $invite = InvoiceInvitation::on($db)->withTrashed()->find($invitation_id);

        return $invite->invoice;
    }

    public function render()
    {
        return render('components.livewire.terms');
    }
}
