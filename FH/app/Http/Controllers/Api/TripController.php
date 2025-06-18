<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ambulance;
use App\Models\RoasterMapping;
use App\Models\TripStatusLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TripController extends Controller
{
    public function storeTrip(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'trip_id' => 'required',
            'trip_details' => 'nullable'
        ]);
    
        $trip = Trip::create([
            'user_id' => $request->user_id,
            'trip_id' => $request->trip_id,
            'trip_details' => $request->trip_details,
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Trip stored successfully.',
            'data' => $trip,
        ], 201);
    }
    
    public function getLatestTrip(Request $request)
    {
        //dd(Auth::user());
        $user = Auth::user(); // Get the logged-in user
        
        $latestTrip = Ambulance::where('user_id', $user->id)->latest()->first();
        
        if($latestTrip)
        {
            
            $roaster = RoasterMapping::where('driver_id', $latestTrip->driver_id)
            ->orderBy('created_at', 'desc')
            ->first();
        
        
            $latestTrip['vehicle'] = $roaster->vehicle ?? '';
    
            if ($latestTrip) {
                
                $track_log = TripStatusLog::where('trip_id', $latestTrip->id)
                    ->where('status', '=', 'Complete')
                    ->first();
            
                if ($track_log) {
                    return response()->json(['message' => 'No trips found for today'], 200);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Latest trip retrieved successfully',
                    'data' => $latestTrip,
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No trips found for the user',
        ], 404);
    }
    
    public static function getTripById(Request $request)
    {
        $request->validate([
            'trip_id' => 'required',
        ]);
        
        $getTrip = Ambulance::where('id', $request->trip_id)->first();
        
        $roaster = RoasterMapping::where('driver_id', $getTrip->driver_id)
            ->orderBy('created_at', 'desc')
            ->first();
        
        
        $getTrip['vehicle'] = $roaster->vehicle ?? '';
        
        return response()->json([
            'success' => true,
            'data' => $getTrip,
        ], 201);

    }

    
}
