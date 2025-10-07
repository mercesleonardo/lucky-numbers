<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Contest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lottery_game_id',
        'draw_number',
        'draw_date',
        'location',
        'numbers',
        'has_accumulated',
        'next_draw_number',
        'next_draw_date',
        'estimated_prize_next_draw',
        'extra_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'draw_date'                 => 'date',
            'numbers'                   => 'array',
            'has_accumulated'           => 'boolean',
            'next_draw_date'            => 'date',
            'estimated_prize_next_draw' => 'decimal:2',
            'extra_data'                => 'array',
            'created_at'                => 'datetime',
            'updated_at'                => 'datetime',
        ];
    }

    public function lotteryGame(): BelongsTo
    {
        return $this->belongsTo(LotteryGame::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }
}
