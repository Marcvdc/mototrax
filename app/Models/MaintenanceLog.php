<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bike_id',
        'title',
        'description',
        'km_at_maintenance',
        'cost',
        'date',
        'type',
    ];

    protected $casts = [
        'km_at_maintenance' => 'integer',
        'cost' => 'decimal:2',
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bike(): BelongsTo
    {
        return $this->belongsTo(Bike::class);
    }

    public function getFormattedCostAttribute(): string
    {
        return '€' . number_format($this->cost, 2, ',', '.');
    }

    public static function getMaintenanceTypes(): array
    {
        return [
            'oil_change' => 'Oil Change',
            'tire_change' => 'Tire Change',
            'chain_service' => 'Chain Service',
            'brake_service' => 'Brake Service',
            'general_service' => 'General Service',
            'repair' => 'Repair',
            'other' => 'Other',
        ];
    }
}
