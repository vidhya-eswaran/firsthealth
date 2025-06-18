<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ambulance;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Http\Controllers\Api\CRMController;


class InsertNonEmergencyTripsToZoho extends Command
{
    protected $signature = 'cron:insert-non-emergency-trips';
    protected $description = 'Insert non-emergency trips scheduled within the next 2 hours into Zoho CRM';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now = Carbon::now(); // Current timestamp
        $twoHoursLater = Carbon::now()->addHours(2); // 2 hours from now
    
        // Fetch non-emergency trips scheduled within the next 2 hours
        $trips = Ambulance::where('trip', 'Non Emergency Trip')->where('driver_id', NULL)
            ->whereBetween('pickup_date', [
                $now->format('d-m-Y H:i:s'),  // Format to match DB format
                $twoHoursLater->format('d-m-Y H:i:s')
            ])
            ->get();
      //  dd($trips);

        if ($trips->isEmpty()) {
            $this->info("No non-emergency trips found for the next 2 hours.");
            return;
        }

        foreach ($trips as $trip) 
        {
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
                
               // dd($startDate);
            
                $zohoData = [
                    'data' => [
                        [
                             'Assign_Ambulance' => $trip->zoho_record_id,
                        ],
                    ],
                ];
            
                $module = 'NE_Driver_Assignment';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
               // dd($zohoData);
            
                $response = Http::withHeaders([
                    'Authorization' => "Zoho-oauthtoken $accessToken",
                    'Content-Type' => 'application/json',
                ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
                
                //dd($response->json());

            $responseData = $response->json();
            if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                $this->info("Trip ID {$trip->id} inserted successfully into Zoho CRM.");
            } else {
                $this->error("Failed to insert Trip ID {$trip->id} into Zoho CRM.");
            }
        }
    }
}
