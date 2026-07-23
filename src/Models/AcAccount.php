<?php

namespace ME\AccSfl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AcAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_accounts';

    protected $fillable = [
        'name',
        'employee_id',
        'designation',
        'user_id',
        'branch_id',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(AcBranch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function balanceReceives(): HasMany
    {
        return $this->hasMany(AcBalanceReceive::class, 'account_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(AcExpense::class, 'account_id');
    }

    public function expenseIous(): HasMany
    {
        return $this->hasMany(AcExpenseIou::class, 'account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AcTransaction::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Restricts account dropdown/filter options to the logged-in user's own linked
     * account(s), for users who have one (e.g. a branch cashier tied to a specific
     * cash account). Users with no linked account (e.g. accounting admins) still see
     * every account, since they aren't "an accounts user" in that sense.
     */
    public function scopeVisibleToCurrentUser(Builder $query): Builder
    {
        $userId = Auth::id();

        if ($userId && static::query()->where('user_id', $userId)->exists()) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }

    public function isReferenced(): bool
    {
        return $this->balanceReceives()->exists()
            || $this->expenses()->exists()
            || $this->expenseIous()->exists()
            || $this->transactions()->exists();
    }
}
