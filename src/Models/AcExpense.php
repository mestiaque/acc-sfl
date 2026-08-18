<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcExpense extends Model
{
    use ActivityLoggable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_expenses';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'expense_no',
        'expense_date',
        'payment_method_id',
        'branch_id',
        'account_id',
        'company_name',
        'receiver_name',
        'receiver_mobile',
        'employee_id',
        'invoice',
        'total_amount',
        'description',
        'attachment',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
        'approval_remarks',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'total_amount' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * References an HR employee (ME\Hr\Models\HrEmployee), not a system login user - mirrors
     * AcExpenseIou::employee(). HR is an optional integration for this module, so this class
     * is referenced by name only when the relation is actually used.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(\ME\Hr\Models\HrEmployee::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AcExpenseDetail::class, 'expense_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(AcTransaction::class, 'reference');
    }

    /**
     * Multiple supporting documents for this expense (receipts, invoices, ...),
     * stored in the central files table. Independent of the legacy single
     * `attachment` column, which stays as-is for older records.
     *
     * Deliberately not a morphMany: this app's other fileable_type columns
     * (User, HrEmployee, General, ...) all store the raw model class name, but
     * acc-sfl registers a Relation::morphMap() for its own `ac_transactions`
     * ledger, which would make a real morphMany store the short alias ('ac_expense')
     * here instead — inconsistent with every other row in the files table.
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\File::class, 'fileable_id')
            ->where('fileable_type', self::class)
            ->where('use_case', 'attachment')
            ->orderBy('created_at');
    }

    public function isReferenced(): bool
    {
        return $this->transactions()->exists();
    }
}
