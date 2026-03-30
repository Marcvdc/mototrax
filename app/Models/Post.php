<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'type',
        'route_id',
        'maintenance_log_id',
        'likes_count',
        'comments_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'comments_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(MaintenanceLog::class);
    }

    public function getFormattedTypeAttribute(): string
    {
        return match($this->type) {
            'text' => 'Text Post',
            'route_share' => 'Route Share',
            'maintenance' => 'Maintenance Update',
            default => 'Post',
        };
    }

    public function getDisplayContentAttribute(): string
    {
        return match($this->type) {
            'route_share' => $this->content . ($this->route ? " 📍 {$this->route->name}" : ''),
            'maintenance' => $this->content . ($this->maintenanceLog ? " 🔧 {$this->maintenanceLog->title}" : ''),
            default => $this->content,
        };
    }

    public static function getPostTypes(): array
    {
        return [
            'text' => 'Text Post',
            'route_share' => 'Route Share',
            'maintenance' => 'Maintenance Update',
        ];
    }
}
