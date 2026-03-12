<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Controllers\VendorPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorContactHashLoginController extends Controller
{
    /**
     * Logs a user into the client portal using their contact_key
     *
     * @param  string  $contact_key  The contact key
     * @return RedirectResponse
     */
    public function login(string $contact_key)
    {
        return redirect('/vendors/purchase_orders');
    }

    /**
     * @return RedirectResponse
     */
    public function magicLink(string $magic_link)
    {
        return redirect($this->setRedirectPath());
    }

    /**
     * errorPage
     *
     * @return View
     */
    public function errorPage()
    {
        return render('generic.error', ['title' => session()->get('title'), 'notification' => session()->get('notification')]);
    }
}
