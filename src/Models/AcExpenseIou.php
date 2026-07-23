<?php

namespace ME\AccSfl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcExpenseIou extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_expense_ious';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_ADJUSTED = 'Adjusted';

    protected $fillable = [
        'iou_no',
        'account_id',
        'employee_id',
        'payment_method_id',
        'branch_id',
        'issue_date',
        'adjust_date',
        'amount',
        'description',
        'receiver_name',
        'receiver_mobile',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'adjust_date' => 'date',
        'amount' => 'decimal:2',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(AcTransaction::class, 'reference');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAdjusted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ADJUSTED);
    }

    public function isReferenced(): bool
    {
        return $this->transactions()->exists();
    }
}
