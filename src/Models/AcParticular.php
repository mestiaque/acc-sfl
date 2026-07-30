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

class AcParticular extends Model
{
    use ActivityLoggable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_particulars';

    protected $fillable = [
        'master_particular_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function masterParticular(): BelongsTo
    {
        return $this->belongsTo(AcMasterParticular::class, 'master_particular_id');
    }

    public function balanceReceives(): HasMany
    {
        return $this->hasMany(AcBalanceReceive::class, 'particular_id');
    }

    public function expenseDetails(): HasMany
    {
        return $this->hasMany(AcExpenseDetail::class, 'particular_id');
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(AcAccount::class, 'ac_account_particular', 'particular_id', 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return $this->balanceReceives()->exists() || $this->expenseDetails()->exists();
    }
}
