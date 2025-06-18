<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude','is_covered'];

    
    public function scopeNearbyAndCovered($query, $latitude, $longitude, $radius = 15)
    {
       return $query->where('is_covered', true)
            ->select(DB::raw("*,
                ( 6371 * acos( cos( radians($latitude) ) 
                * cos( radians( latitude ) ) 
                * cos( radians( longitude ) - radians($longitude) ) 
                + sin( radians($latitude) ) 
                * sin( radians( latitude ) ) ) ) AS distance"))
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc');
    }
}
