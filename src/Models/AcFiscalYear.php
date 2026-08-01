<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class AcFiscalYear extends Model
{
    use ActivityLoggable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_fiscal_years';

    protected $fillable = [
        'label',
        'start_month',
        'start_year',
        'end_month',
        'end_year',
        'is_active',
    ];

    protected $casts = [
        'start_month' => 'integer',
        'start_year' => 'integer',
        'end_month' => 'integer',
        'end_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function startDate(): Carbon
    {
        return Carbon::create($this->start_year, $this->start_month, 1)->startOfMonth();
    }

    public function endDate(): Carbon
    {
        return Carbon::create($this->end_year, $this->end_month, 1)->endOfMonth();
    }

    public function isReferenced(): bool
    {
        return false;
    }
}
