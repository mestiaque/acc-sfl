<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AcTransaction extends Model
{
    use ActivityLoggable;
    use HasFactory;

    protected $table = 'ac_transactions';

    public const TYPE_OPENING_BALANCE = 'Opening Balance';
    public const TYPE_BALANCE_RECEIVE = 'Balance Receive';
    public const TYPE_EXPENSE = 'Expense';
    public const TYPE_IOU_ISSUE = 'IOU Issue';
    public const TYPE_IOU_ADJUSTMENT = 'IOU Adjustment';

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
}
