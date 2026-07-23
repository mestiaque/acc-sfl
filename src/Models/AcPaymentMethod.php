<?php

namespace ME\AccSfl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcPaymentMethod extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_payment_methods';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(AcExpense::class, 'payment_method_id');
    }

    public function expenseIous(): HasMany
    {
        return $this->hasMany(AcExpenseIou::class, 'payment_method_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return $this->expenses()->exists() || $this->expenseIous()->exists();
    }
}
