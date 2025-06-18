<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Registration;


class LocationController extends Controller
{
    public function getNearbyLocations(Request $request)
    {
        $latitude = $request->input('latitude'); 
        $longitude = $request->input('longitude'); 
        $radius = 15; 

        $locations = Location::nearbyAndCovered($latitude, $longitude, $radius)->get();

        // If at least one location is found, it's covered
        if ($locations->count() > 0) {
            return response()->json([
                'is_covered' => true,
                'message' => 'The location is covered',
                'locations' => $locations,
            ]);
        } else {
            return response()->json([
                'is_covered' => false,
                'message' => 'The location is not covered',
            ]);
        }
    }
    
    public function getAllLocations()
    {
        $locations = Location::all()->map(function ($location) {
            return [
                'name' => $location->name,
                'coordinates' => [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ],
            ];
        });
    
        return response()->json($locations);
    }
    
    // public function store(Request $request)
    // {
    //     // Validate incoming request
    //     $request->validate([
    //         'name' => 'nullable|string|max:255',
    //         'latitude' => 'required|numeric|between:-90,90',
    //         'longitude' => 'required|numeric|between:-180,180',
    //     ]);

    //     // Save the location
    //     $location = Location::create([
    //         'name' => $request->name,
    //         'latitude' => $request->latitude,
    //         'longitude' => $request->longitude,
    //     ]);

    //     // Return success response
    //     return response()->json([
    //         'message' => 'Location added successfully.',
    //         'location' => $location,
    //     ], 201);
    // }
    
   public function checkCoverage(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    
        $currentPoint = ['lat' => $request->lat, 'lng' => $request->lng];
    
        // Fetch all locations (assumes all locations form one polygon)
        $locations = DB::table('locations')->select('latitude', 'longitude')->get();
    
        // Convert to array of points
        $polygon = $locations->map(function ($location) {
            return ['lat' => $location->latitude, 'lng' => $location->longitude];
        })->toArray();
    
        // Check if the point is inside the polygon
        $isCovered = $this->isPointInPolygon($currentPoint, $polygon);
    
        // Return JSON response
        return response()->json([
            'is_covered' => $isCovered,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
    
        $location = Location::create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_covered' => $request->is_covered,
        ]);
    
        $uncoveredUsers = Registration::where('is_covered', 0)->get();
     
        $boundary = Location::where('name', $request->name)
            ->select('latitude', 'longitude')
            ->get()
            ->map(function ($loc) {
                return ['lat' => $loc->latitude, 'lng' => $loc->longitude];
            })->toArray();
    //dd($boundary);
        $newlyCoveredUsers = [];
        foreach ($uncoveredUsers as $user) {
            $point = ['lat' => $user->latitude, 'lng' => $user->longitude];
    
            if ($this->isPointInPolygon($point, $boundary)) {
                $user->is_covered = 1;
                $user->save();
    
                $newlyCoveredUsers[] = $user;
            }
        }
   
        foreach ($newlyCoveredUsers as $user) {
            Mail::to($user->email)->send(new UserCoveredNotification($user));
        }
    
        return response()->json([
            'message' => 'Location added successfully, and notifications sent to newly covered users.',
        ]);
    }
    
    private function isPointInPolygon($point, $polygon)
    {
        $x = $point['lat'];
        $y = $point['lng'];
        $inside = false;
    
        for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];
    
            $intersect = (($yi > $y) != ($yj > $y)) &&
                         ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }
    
        return $inside;
    }
}

