<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteZohoDistanceRecords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId, $driverId, $hospitalId, $accessToken;

    public function __construct($userId, $driverId, $hospitalId, $accessToken)
    {
        $this->userId = $userId;
        $this->driverId = $driverId;
        $this->hospitalId = $hospitalId;
        $this->accessToken = $accessToken;
    }

   public function handle()
    {
        $accessToken = $this->accessToken;
        $userId = $this->userId;
        $driverId = $this->driverId;
        $hospitalId = $this->hospitalId;
    
        $driverModule = "Driver_Distance";
        $hospitalModule = "Hospital_Distance";
    
        // 1️⃣ --- DRIVER DISTANCE ---
    
        // Get all active records for this user
        $driverSearchUrl = "https://www.zohoapis.com/crm/v2/$driverModule/search?criteria=((User_Id:equals:$userId)and(Status:equals:Active))";
    
        $driverResponse = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken $accessToken",
            'Content-Type'  => 'application/json',
        ])->get($driverSearchUrl);
    
        $driverData = $driverResponse->json();
        
        Log::info('Zoho Driver Distance API response', [
            'url' => $driverSearchUrl,
            'response' => $driverData
        ]);

    
        $inactiveUpdated = false;
    
        if (!empty($driverData['data'])) {
            foreach ($driverData['data'] as $record) {
                $recordId = $record['id'];
    
                if ($record['Driver_Id'] == $driverId && !$inactiveUpdated) {
                    // 🔄 Update status to In Active
                    $updateResponse = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type'  => 'application/json',
                    ])->put("https://www.zohoapis.com/crm/v2/$driverModule", [
                        'data' => [
                            [
                                'id' => $recordId,
                                'Status' => 'In Active',
                            ]
                        ]
                    ]);
    
                    if (!$updateResponse->successful()) {
                        Log::error("Failed to update Driver_Distance record to In Active", [
                            'id' => $recordId,
                            'response' => $updateResponse->json()
                        ]);
                    } else {
                        $inactiveUpdated = true;
                    }
                } elseif ($record['Driver_Id'] != $driverId) {
                    // ❌ Delete the record
                    $deleteResponse = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type'  => 'application/json',
                    ])->delete("https://www.zohoapis.com/crm/v2/$driverModule/$recordId");
    
                    if (!$deleteResponse->successful()) {
                        Log::error("Failed to delete Driver_Distance record", [
                            'id' => $recordId,
                            'response' => $deleteResponse->json()
                        ]);
                    }
                }
            }
        }
    
        // 2️⃣ --- HOSPITAL DISTANCE ---
        $page = 1;
        $inactiveHospitalUpdated = false;
        
        do {
            $hospitalSearchUrl = "https://www.zohoapis.com/crm/v2/Hospital_Distance/search?criteria=((User_Id:equals:$userId)and(Status:equals:Active))&page=$page&per_page=200";
        
            $hospitalResponse = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type'  => 'application/json',
            ])->get($hospitalSearchUrl);
        
            $hospitalData = $hospitalResponse->json();
        
            if (!empty($hospitalData['data'])) {
                foreach ($hospitalData['data'] as $record) {
                    $recordId = $record['id'];
        
                    if ($record['Hospital_Id'] == $hospitalId && !$inactiveHospitalUpdated) {
                        // Update matching hospital record to In Active
                        $updateResponse = Http::withHeaders([
                            'Authorization' => "Zoho-oauthtoken $accessToken",
                            'Content-Type'  => 'application/json',
                        ])->put("https://www.zohoapis.com/crm/v2/Hospital_Distance", [
                            'data' => [
                                [
                                    'id' => $recordId,
                                    'Status' => 'In Active',
                                ]
                            ]
                        ]);
        
                        if (!$updateResponse->successful()) {
                            Log::error("Failed to update Hospital_Distance to In Active", [
                                'id' => $recordId,
                                'response' => $updateResponse->json()
                            ]);
                        } else {
                            $inactiveHospitalUpdated = true;
                        }
        
                    } elseif ($record['Hospital_Id'] != $hospitalId) {
                        // Delete immediately
                        $deleteResponse = Http::withHeaders([
                            'Authorization' => "Zoho-oauthtoken $accessToken",
                            'Content-Type'  => 'application/json',
                        ])->delete("https://www.zohoapis.com/crm/v2/Hospital_Distance/$recordId");
        
                        if (!$deleteResponse->successful()) {
                            Log::error("Failed to delete Hospital_Distance", [
                                'id' => $recordId,
                                'response' => $deleteResponse->json()
                            ]);
                        }
        
                        usleep(100000); // Delay 100ms
                    }
                }
        
                $page++; // Go to next page
            } else {
                break; // No more records
            }
        } while (!empty($hospitalData['data']));

    }

}
