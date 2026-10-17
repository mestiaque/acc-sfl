<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasAudit;

class AcTransaction extends Model
{
    use HasAudit;
    use ActivityLoggable;
    use HasFactory;

    protected $table = 'ac_transactions';

    public const TYPE_OPENING_BALANCE = 'Opening Balance';
    public const TYPE_BALANCE_RECEIVE = 'Balance Receive';
    public const TYPE_EXPENSE = 'Expense';
    public const TYPE_IOU_ISSUE = 'IOU Issue';
    public const TYPE_IOU_ADJUSTMENT = 'IOU Adjustment';
    public const TYPE_IOU_CORRECTION = 'IOU Correction';

    /** Stored type values stay as-is; this is only what the screens show. */
    public static function typeLabel(?string $type): ?string
    {
        return $type === self::TYPE_BALANCE_RECEIVE ? 'Cash Receive' : $type;
    }

    protected $fillable = [
        'transaction_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'branch_id',
        'account_id',
        'payment_method_id',
        'debit',
        'credit',
        'balance',
        'description',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(AcBranch::class, 'branch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AcAccount::class, 'account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(AcPaymentMethod::class, 'payment_method_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Adds `running_balance`: the account's balance right after this transaction in date order
     * (same-day entries in entry order). The stored `balance` column is the balance at the
     * moment of posting, so it stops being sequential once anything is entered back-dated.
     */
    public function scopeWithRunningBalance(Builder $query): Builder
    {
        return $query->select('ac_transactions.*')->selectSub(fn ($sub) => $sub
            ->from('ac_transactions as rb')
            ->selectRaw('COALESCE(SUM(rb.debit - rb.credit), 0)')
            ->whereColumn('rb.account_id', 'ac_transactions.account_id')
            ->where(fn ($w) => $w
                ->whereColumn('rb.transaction_date', '<', 'ac_transactions.transaction_date')
                ->orWhere(fn ($same) => $same
                    ->whereColumn('rb.transaction_date', 'ac_transactions.transaction_date')
                    ->whereColumn('rb.id', '<=', 'ac_transactions.id'))),
            'running_balance');
    }

    /** Newest date first; same-day entries newest-entered first. */
    public function scopeChronologicalDesc(Builder $query): Builder
    {
        return $query->orderByDesc('transaction_date')->orderByDesc('id');
    }
}
