<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Services\EDocument\Gateway\Qvalia;

use App\Enum\HttpVerb;

class Partner
{
    private string $partner_number;

    public function __construct(public Qvalia $qvalia)
    {
        $this->partner_number = config('ninja.qvalia_partner_number');
    }

    /**
     * getAccount
     *
     * Get Partner Account Object
     *
     * @return mixed
     */
    public function getAccount()
    {
        $uri = "/partner/{$this->partner_number}/account";

        $r = $this->qvalia->httpClient($uri, (HttpVerb::GET)->value, []);

        return $r->object();
    }

    /**
     * getPeppolId
     *
     * Get information on a peppol ID
     *
     * @return mixed
     */
    public function getPeppolId(string $id)
    {
        $uri = "/partner/{$this->partner_number}/peppol/lookup/{$id}";

        $uri = "/partner/{$this->partner_number}/account";

        $r = $this->qvalia->httpClient($uri, (HttpVerb::GET)->value, []);

        return $r->object();

    }

    /**
     * getAccountId
     *
     * Get information on a Invoice Ninja Peppol Client Account
     *
     * @return mixed
     */
    public function getAccountId(string $id)
    {
        $uri = "/partner/{$this->partner_number}/account/{$id}";
    }

    /**
     * createAccount
     *
     * Create a new account for the partner
     *
     * @return mixed
     */
    public function createAccount(array $data)
    {
        $uri = "/partner/{$this->partner_number}/account";

        return $this->qvalia->httpClient($uri, (HttpVerb::POST)->value, $data)->object();
    }

    /**
     * updateAccount
     *
     * Update an existing account for the partner
     *
     * @return mixed
     */
    public function updateAccount(string $accountRegNo, array $data)
    {
        $uri = "/partner/{$this->partner_number}/account/{$accountRegNo}";

        return $this->qvalia->httpClient($uri, (HttpVerb::PUT)->value, $data)->object();
    }

    /**
     * deleteAccount
     *
     * Delete an account for the partner
     *
     * @return mixed
     */
    public function deleteAccount(string $accountRegNo)
    {
        $uri = "/partner/{$this->partner_number}/account/{$accountRegNo}";

        return $this->qvalia->httpClient($uri, (HttpVerb::DELETE)->value, [])->object();
    }

    /**
     * updatePeppolId
     *
     * Update a Peppol ID for an account
     *
     * @return mixed
     */
    public function updatePeppolId(string $accountRegNo, string $peppolId, array $data)
    {
        $uri = "/partner/{$this->partner_number}/account/{$accountRegNo}/peppol/{$peppolId}";

        return $this->qvalia->httpClient($uri, (HttpVerb::PUT)->value, $data)->object();
    }

    /**
     * deletePeppolId
     *
     * Delete a Peppol ID for an account
     *
     * @return mixed
     */
    public function deletePeppolId(string $accountRegNo, string $peppolId)
    {
        $uri = "/partner/{$this->partner_number}/account/{$accountRegNo}/peppol/{$peppolId}";

        return $this->qvalia->httpClient($uri, (HttpVerb::DELETE)->value, [])->object();
    }
}
