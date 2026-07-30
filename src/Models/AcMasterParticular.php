<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcMasterParticular extends Model
{
    use ActivityLoggable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_master_particulars';

    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function particulars(): HasMany
    {
        return $this->hasMany(AcParticular::class, 'master_particular_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDebit(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    public function scopeCredit(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    public function isReferenced(): bool
    {
        return $this->particulars()->exists();
    }
}
