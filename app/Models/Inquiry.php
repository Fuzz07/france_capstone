<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_email',
        'subject',
        'message',
        'fcm_token',
        'status',
        'response',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function respondent()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
