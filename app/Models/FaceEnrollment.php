<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'face_embedding',
        'photo_url',
        'face_features',
        'is_active',
    ];

    protected $casts = [
        'face_embedding' => 'array',
        'face_features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the face enrollment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get face enrollment for a user.
     */
    public static function getForUser($userId)
    {
        return static::where('user_id', $userId)
            ->active()
            ->first();
    }
}
