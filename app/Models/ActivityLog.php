<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to log an activity.
     */
    public static function log($action, $description, $user = null)
    {
        $user = $user ?: auth()->user();
        
        return self::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Guest',
            'user_role' => $user ? $user->role : 'guest',
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
