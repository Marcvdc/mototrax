<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    /** @use HasFactory<\Database\Factories\RouteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'gpx_file',
        'tags',
        'distance',
        'estimated_time',
        'difficulty',
    ];

    protected $casts = [
        'tags' => 'array',
        'distance' => 'decimal:2',
        'estimated_time' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getGpxUrlAttribute(): string
    {
        return asset('storage/' . $this->gpx_file);
    }

    public function getFormattedDistanceAttribute(): string
    {
        return number_format($this->distance, 1) . ' km';
    }

    public function getFormattedTimeAttribute(): string
    {
        $hours = floor($this->estimated_time / 60);
        $minutes = $this->estimated_time % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }
        
        return $minutes . ' min';
    }

    public static function getDifficultyLevels(): array
    {
        return [
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard',
        ];
    }

    public static function getCommonTags(): array
    {
        return [
            'scenic' => 'Scenic',
            'curvy' => 'Curvy',
            'mountain' => 'Mountain',
            'coastal' => 'Coastal',
            'forest' => 'Forest',
            'no_highway' => 'No Highway',
            'historic' => 'Historic',
            'food_stops' => 'Food Stops',
        ];
    }
}
