<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2026. Invoice Ninja LLC (https://invoiceninja.com)
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Models;

use App\Services\Bank\BankService;
use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\BankTransaction
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $bank_integration_id
 * @property int $transaction_id
 * @property string $nordigen_transaction_id
 * @property float $amount
 * @property string|null $currency_code
 * @property int|null $currency_id
 * @property string|null $account_type
 * @property int|null $category_id
 * @property int|null $ninja_category_id
 * @property string $category_type
 * @property string $base_type
 * @property string|null $date
 * @property int $bank_account_id
 * @property string|null $description
 * @property string|null $participant
 * @property string|null $participant_name
 * @property string|null $invoice_ids
 * @property string|null $expense_id
 * @property int|null $vendor_id
 * @property int $status_id
 * @property bool $is_deleted
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $deleted_at
 * @property int|null $bank_transaction_rule_id
 * @property int|null $payment_id
 * @property-read BankIntegration $bank_integration
 * @property-read Company $company
 * @property-read Expense|null $expense
 * @property-read mixed $hashed_id
 * @property-read User $user
 * @property-read Vendor|null $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel company()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel exclude($columns)
 * @method static \Database\Factories\BankTransactionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction filter(\App\Filters\QueryFilters $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel scope()
 * @method static \Illuminate\Database\Eloquent\Builder|BankTransaction withoutTrashed()
 *
 * @property-read Payment|null $payment
 *
 * @mixin \Eloquent
 */
class BankTransaction extends BaseModel
{
    use Filterable;
    use MakesHash;
    use SoftDeletes;

    public const STATUS_UNMATCHED = 1;

    public const STATUS_MATCHED = 2;

    public const STATUS_CONVERTED = 3;

    protected $fillable = [
        'currency_id',
        'category_id',
        'ninja_category_id',
        'date',
        'description',
        'base_type',
        'expense_id',
        'vendor_id',
        'amount',
        'participant',
        'participant_name',
        'currency_code',
    ];

    public function getInvoiceIds()
    {
        $collection = collect();

        $invoices = explode(',', $this->invoice_ids);

        if (count($invoices) >= 1) {
            foreach ($invoices as $invoice) {
                if (is_string($invoice) && strlen($invoice) > 1) {
                    $collection->push($this->decodePrimaryKey($invoice));
                }
            }
        }

        return $collection;
    }

    public function getExpenseIds()
    {
        $collection = collect();

        $expenses = explode(',', $this->expense_id);

        if (count($expenses) >= 1) {
            foreach ($expenses as $expense) {
                if (is_string($expense) && strlen($expense) > 1) {
                    $collection->push($this->decodePrimaryKey($expense));
                }
            }
        }

        return $collection;
    }

    public function getEntityType()
    {
        return self::class;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function bank_integration(): BelongsTo
    {
        return $this->belongsTo(BankIntegration::class)->withTrashed();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }

    // public function expense(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    // {
    //     return $this->belongsTo(Expense::class)->withTrashed();
    // }

    public function service(): BankService
    {
        return new BankService($this);
    }

    public function getExpenses()
    {
        return Expense::whereIn('id', $this->getExpenseIds())->get();
    }
}
