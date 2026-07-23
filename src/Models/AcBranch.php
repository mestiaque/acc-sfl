<?php

namespace ME\AccSfl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcBranch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_branches';

    protected $fillable = [
        'name',
        'code',
        'location',
        'branch_head',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(AcAccount::class, 'branch_id');
    }

    public function balanceReceives(): HasMany
    {
        return $this->hasMany(AcBalanceReceive::class, 'branch_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(AcExpense::class, 'branch_id');
    }

    public function expenseIous(): HasMany
    {
        return $this->hasMany(AcExpenseIou::class, 'branch_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AcTransaction::class, 'branch_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return $this->accounts()->exists()
            || $this->balanceReceives()->exists()
            || $this->expenses()->exists()
            || $this->expenseIous()->exists()
            || $this->transactions()->exists();
    }
}
