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

    protected $fillable = [
        'expense_no',
        'expense_date',
        'payment_method_id',
        'branch_id',
        'account_id',
        'company_name',
        'receiver_name',
        'receiver_mobile',
        'total_amount',
        'description',
        'attachment',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'total_amount' => 'decimal:2',
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

    public function details(): HasMany
    {
        return $this->hasMany(AcExpenseDetail::class, 'expense_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(AcTransaction::class, 'reference');
    }

    public function isReferenced(): bool
    {
        return $this->transactions()->exists();
    }
}
