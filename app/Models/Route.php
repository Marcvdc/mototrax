<?php

namespace App\Models;

use Database\Factories\RouteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    /** @use HasFactory<RouteFactory> */
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
        'is_public',
        'bbox',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'waypoint_count',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'bbox' => 'array',
            'distance' => 'decimal:3',
            'estimated_time' => 'integer',
            'is_public' => 'boolean',
            'start_lat' => 'decimal:7',
            'start_lng' => 'decimal:7',
            'end_lat' => 'decimal:7',
            'end_lng' => 'decimal:7',
            'waypoint_count' => 'integer',
        ];
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

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
        return route('api.routes.gpx', ['route' => $this->id]);
    }

    public function getFormattedDistanceAttribute(): string
    {
        return number_format($this->distance, 1).' km';
    }

    public function getFormattedTimeAttribute(): string
    {
        $hours = floor($this->estimated_time / 60);
        $minutes = $this->estimated_time % 60;

        if ($hours > 0) {
            return $hours.'h '.$minutes.'min';
        }

        return $minutes.' min';
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
