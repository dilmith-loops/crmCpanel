<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'holidays';

    protected $fillable = [
        'date',
        'name',
        'type',
        'description',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a given date string or Carbon instance is a configured holiday.
     */
    public static function isHoliday($date): bool
    {
        $d = ($date instanceof Carbon) ? $date->format('Y-m-d') : substr((string)$date, 0, 10);
        return self::where('date', $d)->exists();
    }

    /**
     * Get a keyed map of all holidays for frontend lookup:
     * [
     *   '2026-10-25' => ['id' => 1, 'date' => '2026-10-25', 'name' => 'Diwali', 'type' => 'public'],
     *   ...
     * ]
     */
    public static function getHolidaysMap(?string $month = null): array
    {
        HolidayType::ensureDefaults();
        $typeMap = HolidayType::pluck('name', 'code')->toArray();
        $colorMap = HolidayType::pluck('color', 'code')->toArray();

        $query = self::orderBy('date');
        if ($month) {
            $query->where('date', 'like', $month . '%');
        }
        $holidays = $query->get();

        $map = [];
        foreach ($holidays as $h) {
            $dateStr = $h->date instanceof Carbon ? $h->date->format('Y-m-d') : substr((string)$h->date, 0, 10);
            $typeCode = $h->type ?: 'public';
            $map[$dateStr] = [
                'id' => $h->id,
                'date' => $dateStr,
                'name' => $h->name ?: 'Holiday',
                'type' => $typeCode,
                'type_name' => $typeMap[$typeCode] ?? ucwords(str_replace('_', ' ', $typeCode)),
                'type_color' => $colorMap[$typeCode] ?? 'amber',
                'description' => $h->description,
            ];
        }
        return $map;
    }
}
