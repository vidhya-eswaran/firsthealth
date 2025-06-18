<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getCreatedAtFormattedAttribute()
    {
        $createdAt = $this->created_at;

        if ($createdAt->isToday()) {
            return 'Today at ' . $createdAt->format('h:i A');
        } elseif ($createdAt->isYesterday()) {
            return 'Yesterday at ' . $createdAt->format('h:i A');
        }

        return $createdAt->format('Y-m-d h:i A');  // Default format for other dates
    }
}
