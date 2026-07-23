<?php

namespace ME\AccSfl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcBalanceReceive extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_balance_receives';

    protected $fillable = [
        'receive_no',
        'receive_date',
        'branch_id',
        'account_id',
        'particular_id',
        'amount',
        'description',
        'attachment',
        'created_by',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(AcBranch::class, 'branch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AcAccount::class, 'account_id');
    }

    public function particular(): BelongsTo
    {
        return $this->belongsTo(AcParticular::class, 'particular_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
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
