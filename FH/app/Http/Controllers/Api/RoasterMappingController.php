<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\RoasterMapping;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Hospital;
use Illuminate\Support\Str;

use App\Models\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class RoasterMappingController extends Controller
{
    /**
     * Insert or Update Roaster Mapping
     */
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
          //  'id'            => 'nullable|exists:roaster_mapping,id',
            'hospital'      => 'nullable',
            'hospital_id'   => 'nullable',
            'paramedic_id'  => 'nullable',
            'driver_id'     => 'nullable',
            'driver_name'   => 'nullable',
            'vehicle'       => 'nullable',
            'vehicle_id'    => 'nullable',
            'driver_status' => 'nullable',
            'ride_status'   => 'nullable',
            'shift' => 'nullable',
            'zoho_record_id' => 'nullable'
        ]);
        
        //dd($request);

        if ($request->id) {
            // If ID is provided, update the record
            $roaster = RoasterMapping::find($request->id);
            $roaster->update($request->all());

            return response()->json([
                'message' => 'Roaster mapping record updated successfully',
                'data' => $roaster
            ], 200);
        } else {
            // If ID is not provided, create a new record
            $roaster = RoasterMapping::create($request->all());

            return response()->json([
                'message' => 'Roaster mapping record created successfully',
                'data' => $roaster
            ], 201);
        }
    }
    
    public function getHospitalsByDistance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'location' => 'required'
        ]);
    
        $userLat = $request->lat;
        $userLong = $request->long;
    
        $hospitals = DB::table('hospitals')
            ->select(
                'id',
                'name',
                'latitude',
                'longitude',
                DB::raw("ROUND(6371 * ACOS(
                    COS(RADIANS($userLat)) * COS(RADIANS(latitude)) *
                    COS(RADIANS(longitude) - RADIANS($userLong)) +
                    SIN(RADIANS($userLat)) * SIN(RADIANS(latitude))
                ), 2) AS distance")
            )
            ->orderBy('distance', 'DESC')
            ->get();
        
        //dd($hospitals);
        
        $driverIds = RoasterMapping::where('driver_status', 'Online')
            ->where(function ($query) {
                $query->where('ride_status', 'Complete')
                      ->orWhereNull('ride_status');
            })
            ->pluck('driver_id')
            ->toArray(); // ✅ flatten
            
           // dd($driverIds);
        
           $drivers = Driver::whereIn('id', $driverIds)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_long')
            ->get()
            ->map(function ($driver) use ($userLat, $userLong) {
                $theta = $userLong - $driver->current_long;
                $dist = sin(deg2rad($userLat)) * sin(deg2rad($driver->current_lat)) +
                        cos(deg2rad($userLat)) * cos(deg2rad($driver->current_lat)) * cos(deg2rad($theta));
                $dist = acos($dist);
                $dist = rad2deg($dist);
                $miles = $dist * 60 * 1.1515;
                $driver->distance = round($miles * 1.609344, 2); // in km
                return $driver;
            })->sortBy('distance')->values();

        //  dd($drivers);

        
         $zohoData = [
            'data' => $hospitals->map(function ($hospital) use ($request) {
                return [
                    'Name'             => Str::limit($hospital->name . ' | ' . ($hospital->distance ?? '0') . ' km', 100),
                    'Hospital_Name'    => Str::limit($hospital->name . ' | ' . ($hospital->distance ?? '0') . ' km', 100),
                    'Hospital_Id'      => $hospital->id,
                    'User_Id'          => $request->user_id,
                    'Location_From'    => Str::limit($request->location, 100),
                    'Status'           => "Active"
                ];
            })->toArray()
        ];
        
        $crmController = new CRMController();
        $accessToken = $crmController->getZohoAccessToken();
    
        $module = 'Hospital_Distance';
        $chunks = collect($zohoData['data'])->chunk(100);

        foreach ($chunks as $index => $chunk) {
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://www.zohoapis.com/crm/v2/$module", [
                'data' => $chunk->values()->all()
            ]);
        
            $result = $response->json();
        
            // logger("Batch $index", $result);
            // if (isset($result['code']) && $result['code'] !== 'SUCCESS') {
            // dd("Error in batch $index", $result);
            // }
        }
        
        
        $zohoData1 = [
            'data' => $drivers->map(function ($driver) use ($request) {
                $currentLocation = $this->getLocationName($driver->current_lat, $driver->current_long);
    
                return [
                    'Name'             => $driver->name . ' | ' . ($driver->distance ?? '0') . ' km', 
                    'Driver_Name'      => $driver->name . ' | ' . ($driver->distance ?? '0') . ' km', 
                    'Driver_Id'        => $driver->id,
                    'User_Id'          => $request->user_id, // Corrected user_id from Driver table
                    'Current_Location' => $currentLocation,
                    'Driver_Status'    => 'Online',
                    'Location_From'    => $request->location,
                    'Status'           => "Active"
                ];
            })->toArray()
        ];
        
       // dd($zohoData1);
    
        // Step 4: Send Data to Zoho CRM
        $crmController1 = new CRMController();
        $accessToken1 = $crmController1->getZohoAccessToken();
    
        $module = 'Driver_Distance';
        $response1 = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken $accessToken1",
            'Content-Type' => 'application/json',
        ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData1);
        
       // dd($response1->json());
    
        return response()->json([
            'status' => true,
            'hospitals' => $hospitals,
            'drivers' => $drivers
        ]);
    }
    
     private function getLocationName($latitude, $longitude)
    {
        $apiKey = "AIzaSyCcnRShoTxqZ8ZufrDkjjZ29awLWOQ5vUM"; // Ensure you have added this key to your .env file
        $url = "https://maps.googleapis.com/maps/api/geocode/json";
    
        $response = Http::get($url, [
            'latlng' => "$latitude,$longitude",
            'key' => $apiKey,
        ]);
        
        //dd($response->json());
    
        Log::info('Geocoding API Response:', $response->json());
        
        if ($response->successful()) {
            $data = $response->json();
    
            if (isset($data['results'][0]['formatted_address'])) {
                return $data['results'][0]['formatted_address']; // The location name
            }
        }
    
        return 'Unknown Location';
    }
    
    public function checkDriverRecord(Request $request)
    {
        $request->validate([
            'selectedDriver' => 'required',
            'selectedVehicle' => 'required',
            'shift' => 'required',
        ]);
    
        $driverId = $request->selectedDriver;
        $vehicleId = $request->selectedVehicle;
        $shift = $request->shift;
        
        // Get the current date
        $currentDate = now()->format('Y-m-d');
    
        // Check if the record exists in RoasterMapping for the given driver, vehicle, and shift on the current date
        $roaster = RoasterMapping::where('driver_id', $driverId)
            ->where('vehicle_id', $vehicleId)
            ->where('shift', $shift)
            ->whereDate('created_at', $currentDate) // Assuming created_at stores the date of the record
            ->first();
    
        if ($roaster) {
            return response()->json([
                'status' => true,
                'message' => 'Record exists for the current date.',
                'data' => $roaster
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No record found for the current date.',
            ]);
        }
    }

}
