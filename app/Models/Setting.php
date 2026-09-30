<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'green_threshold',
        'yellow_threshold',
        'orange_threshold',
    ];

    protected $casts = [
        'green_threshold' => 'decimal:2',
        'yellow_threshold' => 'decimal:2',
        'orange_threshold' => 'decimal:2',
    ];

    /**
     * Get the current settings instance or return defaults.
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'green_threshold' => 0.85,
                'yellow_threshold' => 0.70,
                'orange_threshold' => 0.55,
            ]
        );
    }
}
