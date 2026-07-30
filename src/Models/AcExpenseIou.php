<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcExpenseIou extends Model
{
    use ActivityLoggable;
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

    /**
     * References an HR employee (ME\Hr\Models\HrEmployee), not a system login user - most
     * employees who take cash advances have no `users` row at all. HR is an optional
     * integration for this module, so this class is referenced by name only when the
     * relation is actually used, never eagerly loaded when HR isn't installed.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(\ME\Hr\Models\HrEmployee::class, 'employee_id');
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
