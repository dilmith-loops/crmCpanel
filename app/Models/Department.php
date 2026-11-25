<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'group',
        'status',
        'hod_ids',
    ];

    protected $casts = [
        'hod_ids' => 'array',
    ];

    /**
     * Get users belonging to this department.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department', 'name');
    }

    /**
     * Get HOD users assigned to this department.
     */
    public function getHodsAttribute()
    {
        $ids = is_array($this->hod_ids) ? array_filter($this->hod_ids) : [];
        $hods = collect();
        if (!empty($ids)) {
            $hods = User::whereIn('id', $ids)->get();
        }

        // Fallback to any user with role HOD and matching department from users list
        if ($hods->isEmpty()) {
            $hods = User::where('role', 'HOD')->where('department', $this->name)->get();
        }

        return $hods;
    }

    /**
     * Check if a specific user is an HOD for this department.
     */
    public function isHod($userOrId): bool
    {
        $id = $userOrId instanceof User ? $userOrId->id : (int)$userOrId;
        $ids = is_array($this->hod_ids) ? array_map('intval', $this->hod_ids) : [];
        if (in_array($id, $ids, true)) {
            return true;
        }

        return User::where('id', $id)
            ->where('role', 'HOD')
            ->where('department', $this->name)
            ->exists();
    }
}

