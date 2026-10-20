<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAudit;
use ME\AccSfl\Models\Concerns\HasAttachmentGallery;

class AcExpenseIou extends Model
{
    use HasAttachmentGallery;
    use HasAudit;
    use ActivityLoggable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_expense_ious';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_ADJUSTED = 'Adjusted';

    /** Approval (separate from the Pending / Adjusted settlement status above). */
    public const APPROVAL_MODULE = 'accounts.expense_iou';
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'iou_no',
        'account_id',
        'employee_id',
        'payment_method_id',
        'branch_id',
        'issue_date',
        'adjust_date',
        'amount',
        'settlement_amount',
        'settlement_payment_method_id',
        'description',
        'receiver_name',
        'receiver_mobile',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_remarks',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'adjust_date' => 'date',
        'amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'approved_at' => 'datetime',
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

    /** Expenses recorded for what was bought with this IOU. */
    public function expenses(): HasMany
    {
        return $this->hasMany(AcExpense::class, 'iou_id');
    }

    public function approvedExpenseTotal(): float
    {
        return (float) $this->expenses()->where('status', AcExpense::STATUS_APPROVED)->sum('total_amount');
    }

    public function hasPendingExpenses(): bool
    {
        return $this->expenses()->where('status', AcExpense::STATUS_PENDING)->exists();
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(AcTransaction::class, 'reference');
    }

    /**
     * Multiple supporting documents for this IOU, stored in the central files table.
     *
     * Deliberately not a morphMany — see AcExpense::attachments() for why: acc-sfl's
     * Relation::morphMap() would make it store the short alias ('ac_expense_iou') here,
     * inconsistent with every other fileable_type in the files table.
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\File::class, 'fileable_id')
            ->where('fileable_type', self::class)
            ->where('use_case', 'attachment')
            ->orderBy('created_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Approved IOUs only — the cash has left the account, so expenses can be recorded against them. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
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
