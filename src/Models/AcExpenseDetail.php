<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcExpenseDetail extends Model
{
    use ActivityLoggable;
    use HasFactory;

    protected $table = 'ac_expense_details';

    public $timestamps = true;

    protected $fillable = [
        'expense_id',
        'particular_id',
        'invoice',
        'qty',
        'uom',
        'rate',
        'amount',
        'description',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(AcExpense::class, 'expense_id');
    }

    public function particular(): BelongsTo
    {
        return $this->belongsTo(AcParticular::class, 'particular_id');
    }
}



