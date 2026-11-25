<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HolidayType extends Model
{
    use HasFactory;

    protected $table = 'holiday_types';

    protected $fillable = [
        'name',
        'code',
        'color',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public static array $defaultTypes = [
        ['name' => 'Public Holiday', 'code' => 'public', 'color' => 'amber', 'is_system' => true],
        ['name' => 'Mercantile Holiday', 'code' => 'mercantile', 'color' => 'indigo', 'is_system' => true],
        ['name' => 'Bank Holiday', 'code' => 'bank', 'color' => 'emerald', 'is_system' => true],
        ['name' => 'Company Holiday', 'code' => 'company', 'color' => 'purple', 'is_system' => true],
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Ensure default system holiday types are present.
     */
    public static function ensureDefaults(): void
    {
        foreach (self::$defaultTypes as $type) {
            self::firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'color' => $type['color'],
                    'is_system' => $type['is_system'],
                ]
            );
        }
    }

    /**
     * Get all holiday types sorted by system first, then name.
     */
    public static function getAllTypes()
    {
        self::ensureDefaults();
        return self::orderBy('is_system', 'desc')->orderBy('name', 'asc')->get();
    }

    /**
     * Generate a unique slug/code from the type name.
     */
    public static function generateUniqueCode(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '_');
        if (empty($base)) {
            $base = 'holiday_type';
        }

        $code = $base;
        $counter = 1;

        while (self::where('code', $code)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $code = $base . '_' . $counter;
            $counter++;
        }

        return $code;
    }
}
