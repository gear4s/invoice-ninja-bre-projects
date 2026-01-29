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

namespace App\Services\Quickbooks;

use App\Models\Company;
use App\DataMapper\QuickbooksSync;
use App\Services\Quickbooks\Models\QbQuote;
use App\Services\Quickbooks\Models\QbClient;
use QuickBooksOnline\API\Core\CoreConstants;
use App\Services\Quickbooks\Models\QbExpense;
use App\Services\Quickbooks\Models\QbInvoice;
use App\Services\Quickbooks\Models\QbPayment;
use App\Services\Quickbooks\Models\QbProduct;
use QuickBooksOnline\API\DataService\DataService;
use App\Services\Quickbooks\Jobs\QuickbooksImport;
use App\Services\Quickbooks\Transformers\IncomeAccountTransformer;

class QuickbooksService
{
    public DataService $sdk;

    public QbInvoice $invoice;

    public QbProduct $product;

    public QbClient $client;

    public QbPayment $payment;

    public QbQuote $quote;

    public QbExpense $expense;

    public QuickbooksSync $settings;

    private bool $testMode = true;

    private bool $try_refresh = true;

    public function __construct(public Company $company)
    {
        $this->init();
    }

    private function init(): self
    {

        if(config('services.quickbooks.client_id'))
        {
            $config = [
                'ClientID' => config('services.quickbooks.client_id'),
                'ClientSecret' => config('services.quickbooks.client_secret'),
                'auth_mode' => 'oauth2',
                'scope' => "com.intuit.quickbooks.accounting",
                'RedirectURI' => config('services.quickbooks.redirect'),
                'baseUrl' => $this->testMode ? CoreConstants::SANDBOX_DEVELOPMENT : CoreConstants::QBO_BASEURL,
            ];

            $merged = array_merge($config, $this->ninjaAccessToken());

            $this->sdk = DataService::Configure($merged);

            $this->sdk->enableLog();
            $this->sdk->setMinorVersion("75");
            $this->sdk->throwExceptionOnError(true);
       
            $this->checkToken();
        }
        
        $this->invoice = new QbInvoice($this);

        $this->quote = new QbQuote($this);

        $this->product = new QbProduct($this);

        $this->client = new QbClient($this);

        $this->payment = new QbPayment($this);

        $this->expense = new QbExpense($this);

        $this->settings = $this->company->quickbooks->settings;

        return $this;
    }

    /**
     * Refresh the service after OAuth token has been updated.
     * This reloads the company from the database and reinitializes the SDK
     * with the new access token.
     *
     * @return self
     */
    public function refresh(): self
    {
        // Reload company from database to get fresh token data
        $this->company = $this->company->fresh();
        
        // Reinitialize the SDK with the updated token
        $this->init();
        
        return $this;
    }

    private function checkToken(): self
    {

        if (!$this->company->quickbooks || $this->company->quickbooks->accessTokenExpiresAt == 0 || $this->company->quickbooks->accessTokenExpiresAt > time()) {
            return $this;
        }

        if ($this->company->quickbooks->accessTokenExpiresAt && $this->company->quickbooks->accessTokenExpiresAt < time() && $this->try_refresh) {


            try{
                $this->sdk()->refreshToken($this->company->quickbooks->refresh_token);
            }
            catch(\Throwable $e){
                nlog("QB: failure to refresh token: " . $e->getMessage());
                $this->disconnect();
                return $this;
            }

            $this->company = $this->company->fresh();
            $this->try_refresh = false;
            $this->init();

            return $this;
        }

        nlog('Quickbooks token expired and could not be refreshed => ' .$this->company->company_key);
        
        throw new \Exception('Quickbooks token expired and could not be refreshed');

    }

    private function ninjaAccessToken(): array
    {
        return $this->company->quickbooks->accessTokenExpiresAt > 0 ? [
            'accessTokenKey' => $this->company->quickbooks->accessTokenKey,
            'refreshTokenKey' => $this->company->quickbooks->refresh_token,
            'QBORealmID' => $this->company->quickbooks->realmID,
        ] : [];
    }

    public function sdk(): SdkWrapper
    {
        return new SdkWrapper($this->sdk, $this->company);
    }

    /**
     *
     *
     * @return void
     */
    public function syncFromQb(): void
    {
        QuickbooksImport::dispatch($this->company->id, $this->company->db);
    }

    public function findEntityById(string $entity, string $id): mixed
    {
        return $this->sdk->FindById($entity, $id);
    }

    public function query(string $query)
    {
        return $this->sdk->Query($query);
    }

    /**
     * Flag to determine if a sync is allowed in either direction
     *
     * @param  string $entity
     * @param  \App\Enum\SyncDirection $direction
     * @return bool
     */
    public function syncable(string $entity, \App\Enum\SyncDirection $direction): bool
    {
        return isset($this->settings->{$entity}->direction) && ($this->settings->{$entity}->direction === $direction || $this->settings->{$entity}->direction === \App\Enum\SyncDirection::BIDIRECTIONAL);
    }


// [
//     QuickBooksOnline\API\Data\IPPAccount {#7706
//       +Id: "30",
//       +SyncToken: "0",
//       +MetaData: QuickBooksOnline\API\Data\IPPModificationMetaData {#7707
//         +CreatedByRef: null,
//         +CreateTime: "2024-05-22T14:46:30-07:00",
//         +LastModifiedByRef: null,
//         +LastUpdatedTime: "2024-05-22T14:46:30-07:00",
//         +LastChangedInQB: null,
//         +Synchronized: null,
//       },
//       +CustomField: null,
//       +AttachableRef: null,
//       +domain: null,
//       +status: null,
//       +sparse: null,
//       +Name: "Uncategorized Income",
//       +SubAccount: "false",
//       +ParentRef: null,
//       +Description: null,
//       +FullyQualifiedName: "Uncategorized Income",
//       +AccountAlias: null,
//       +TxnLocationType: null,
//       +Active: "true",
//       +Classification: "Revenue",
//       +AccountType: "Income",
//       +AccountSubType: "ServiceFeeIncome",
//       +AccountPurposes: null,
//       +AcctNum: null,
//       +AcctNumExtn: null,
//       +BankNum: null,
//       +OpeningBalance: null,
//       +OpeningBalanceDate: null,
//       +CurrentBalance: "0",
//       +CurrentBalanceWithSubAccounts: "0",
//       +CurrencyRef: "USD",
//       +TaxAccount: null,
//       +TaxCodeRef: null,
//       +OnlineBankingEnabled: null,
//       +FIName: null,
//       +JournalCodeRef: null,
//       +AccountEx: null,
//     },
//   ]
    /**
     * Fetch income accounts from QuickBooks.
     * 
     * @return array Array of account objects with 'Id', 'Name', 'AccountType', etc.
     */
    public function fetchIncomeAccounts(): array
    {
        try {
            if (!$this->sdk) {
                return [];
            }
            
            $query = "SELECT * FROM Account WHERE AccountType = 'Income' AND Active = true";
            $accounts = $this->sdk->Query($query);
            

            $iat = new IncomeAccountTransformer();
            $income_accounts = $iat->transformMany($accounts ?? []); //@phpstan-ignore-line return type is @array - but they also spec NULL as well

            return $income_accounts;
        } catch (\Exception $e) {
            nlog("Error fetching income accounts: {$e->getMessage()}");
            return [];
        }
    }


// [
//         QuickBooksOnline\API\Data\IPPAccount {#7709
//       +Id: "57",
//       +SyncToken: "0",
//       +MetaData: QuickBooksOnline\API\Data\IPPModificationMetaData {#7698
//         +CreatedByRef: null,
//         +CreateTime: "2024-05-27T10:17:24-07:00",
//         +LastModifiedByRef: null,
//         +LastUpdatedTime: "2024-05-27T10:17:24-07:00",
//         +LastChangedInQB: null,
//         +Synchronized: null,
//       },
//       +CustomField: null,
//       +AttachableRef: null,
//       +domain: null,
//       +status: null,
//       +sparse: null,
//       +Name: "Workers Compensation",
//       +SubAccount: "true",
//       +ParentRef: "11",
//       +Description: null,
//       +FullyQualifiedName: "Insurance:Workers Compensation",
//       +AccountAlias: null,
//       +TxnLocationType: null,
//       +Active: "true",
//       +Classification: "Expense",
//       +AccountType: "Expense",
//       +AccountSubType: "Insurance",
//       +AccountPurposes: null,
//       +AcctNum: null,
//       +AcctNumExtn: null,
//       +BankNum: null,
//       +OpeningBalance: null,
//       +OpeningBalanceDate: null,
//       +CurrentBalance: "0",
//       +CurrentBalanceWithSubAccounts: "0",
//       +CurrencyRef: "USD",
//       +TaxAccount: null,
//       +TaxCodeRef: null,
//       +OnlineBankingEnabled: null,
//       +FIName: null,
//       +JournalCodeRef: null,
//       +AccountEx: null,
//     },
//   ]
    /**
     * Fetch expense accounts from QuickBooks.
     * 
     * @return array Array of account objects with 'Id', 'Name', 'AccountType', etc.
     */
    public function fetchExpenseAccounts(): array
    {
        try {
            if (!$this->sdk) {
                return [];
            }
            
            $query = "SELECT * FROM Account WHERE AccountType IN ('Expense', 'Cost of Goods Sold') AND Active = true";
            $accounts = $this->sdk->Query($query);
            
            return is_array($accounts) ? $accounts : []; //@phpstan-ignore-line return type is @array - but they also spec NULL 
        } catch (\Exception $e) {
            nlog("Error fetching expense accounts: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Verify the current token can authenticate with QuickBooks.
     * Performs a minimal API call; stub this in tests to avoid real API calls.
     *
     * @return bool True if token is valid and can authenticate
     */
    public function isTokenValid(): bool
    {
        try {
            if (! isset($this->sdk) || ! $this->sdk) {
                return false;
            }
            $this->sdk->Query('SELECT Id FROM CompanyInfo MAXRESULTS 1');
            return true;
        } catch (\Exception $e) {
            nlog('Quickbooks token validation failed: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Format accounts for UI dropdown consumption.
     * 
     * @param array $accounts Raw account objects from QuickBooks API
     * @return array Formatted array with 'value' (ID) and 'label' (Name) for each account
     */
    public function formatAccountsForDropdown(array $accounts): array
    {
        $formatted = [];
        
        foreach ($accounts as $account) {
            $id = is_object($account) && isset($account->Id) 
                ? (string) $account->Id 
                : (is_array($account) && isset($account['Id']) ? (string) $account['Id'] : null);
                
            $name = is_object($account) && isset($account->Name)
                ? (string) $account->Name
                : (is_array($account) && isset($account['Name']) ? (string) $account['Name'] : '');
            
            if ($id && $name) {
                $formatted[] = [
                    'value' => $id,
                    'label' => $name,
                    'account_type' => is_object($account) && isset($account->AccountType)
                        ? (string) $account->AccountType
                        : (is_array($account) && isset($account['AccountType']) ? (string) $account['AccountType'] : ''),
                ];
            }
        }
        
        return $formatted;
    }

    
    /**
     * disconnect
     *
     * revokes the current token.
     * @return self
     */
    public function disconnect(): self
    {

        try {
            $this->sdk()->revokeAccessToken();
        }
        catch(\Throwable $e){
            nlog("QB: failure to revoke token during disconnect:: " . $e->getMessage());
        }

        $this->company->quickbooks = null;
        $this->company->save();

        return $this;
        
    }
}
