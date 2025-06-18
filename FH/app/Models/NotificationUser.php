<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'form_user_id',
        'to_user_id',
        'to_email',
        'type',
        'title',
        'body',
        'is_sent',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

}
