<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AcAccount extends Model
{
    use ActivityLoggable;
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

    /**
     * Optional per-account allow-list of Particulars (see ac_account_particular migration).
     * An account with no rows here is unrestricted - its tied user sees every particular.
     */
    public function particulars(): BelongsToMany
    {
        return $this->belongsToMany(AcParticular::class, 'ac_account_particular', 'account_id', 'particular_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The account the logged-in user is personally tied to (e.g. a branch cashier with
     * their own cash account), or null for users not tied to any single account (e.g.
     * accounting admins, who work across every account).
     */
    public static function currentUserTiedAccount(): ?self
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        return static::query()->where('user_id', $userId)->active()->first();
    }

    /**
     * Particular IDs the logged-in user is restricted to, or null if unrestricted (not
     * tied to an account, or tied to an account with no particular allow-list set).
     *
     * @return array<int>|null
     */
    public static function currentUserAllowedParticularIds(): ?array
    {
        $account = static::currentUserTiedAccount();

        if (! $account) {
            return null;
        }

        $ids = $account->particulars()->pluck('ac_particulars.id')->all();

        return empty($ids) ? null : $ids;
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
