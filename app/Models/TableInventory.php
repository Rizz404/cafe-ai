<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The only source of truth for table availability. Stands in for a real
 * POS / reservation-system adapter for V1: the AI must reach this table (or
 * its future adapter) through a tool call, never answer from its own memory.
 */
#[Fillable([
    'seating_area_id',
    'reservation_date',
    'time_slot',
    'total_tables',
    'reserved_tables',
])]
class TableInventory extends Model
{
    use HasFactory;

    protected $table = 'table_inventory';

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
        ];
    }

    public function seatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class);
    }

    public function availableTables(): int
    {
        return max(0, $this->total_tables - $this->reserved_tables);
    }

    public function isAvailable(): bool
    {
        return $this->availableTables() > 0;
    }
}
