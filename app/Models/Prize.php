<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prize extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contest_id',
        'tier',
        'description',
        'winners',
        'prize_amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'winners'      => 'integer',
            'prize_amount' => 'decimal:2',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    /**
     * Get the contest that owns the prize.
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }
}
