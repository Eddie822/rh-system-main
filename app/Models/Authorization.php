<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Authorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'user_id',
        'authorization_role',
        'action',
        'reason',
    ];

    /**
     * Request beign authorized
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }


    /**
     * User who performed the authorization
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
