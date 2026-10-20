<?php

namespace ME\AccSfl\Models;

use App\Traits\ActivityLoggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAudit;
use ME\AccSfl\Models\Concerns\HasAttachmentGallery;

class AcBalanceReceive extends Model
{
    use HasAttachmentGallery;
    use HasAudit;
    use ActivityLoggable;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ac_balance_receives';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

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
        'status',
        'approved_by',
        'approved_at',
        'approval_remarks',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Supporting documents in the central files table, alongside the legacy single
     * `attachment` column. Not a morphMany - see AcExpense::attachments() for why.
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\File::class, 'fileable_id')
            ->where('fileable_type', self::class)
            ->where('use_case', 'attachment')
            ->orderBy('created_at');
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
