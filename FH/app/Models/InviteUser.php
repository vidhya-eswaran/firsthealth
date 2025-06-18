<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'to_mail',
        'type_dependant',
        'is_accepted',
        'is_removed',
        'type_mail',
        'is_revoke',
        'is_release_slot',
        'status'
    ];

}
