<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller; 

class ZohoController extends Controller
{
    public function redirectToZoho()
    {
        $url = config('app.ZOHO_ACCOUNTS_URL') . '/auth?scope=ZohoCRM.modules.ALL&response_type=code' .
               '&client_id=' . env('ZOHO_CLIENT_ID') .
               '&redirect_uri=' . env('ZOHO_REDIRECT_URI');
        return redirect($url);
    }

    public function handleCallback(Request $request)
    {
        $code = $request->query('code');
    
        if (!$code) {
            return response()->json(['error' => 'Authorization code not received'], 400);
        }
        
        $client_id = "1000.QNLCYYNYN2D209PRU9CAY42GVUKX4C";
        $client_secret = "f20465b33c536ec11cce176fe927a633c7edc07206";
        $redirect_uri = "https://blaccdot.com/FH/public/api/zoho/callback";
    
        $response = Http::post('https://accounts.zoho.com/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code,
        ]);
    
        if ($response->failed()) {
            return response()->json($response->json(), $response->status());
        }
    
        return response()->json($response->json());
    }

    public function getDataFromZoho()
    {
        $accessToken = 'your_access_token'; // Retrieve from database.

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get(env('ZOHO_API_BASE_URL') . '/Leads');

        return response()->json($response->json());
    }

    public function sendDataToZoho(Request $request)
    {
        $accessToken = 'your_access_token'; // Retrieve from database.

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post(env('ZOHO_API_BASE_URL') . '/Leads', [
            'data' => [
                [
                    'Company' => $request->input('company'),
                    'Last_Name' => $request->input('last_name'),
                    'First_Name' => $request->input('first_name'),
                    'Email' => $request->input('email'),
                ],
            ],
        ]);

        return response()->json($response->json());
    }
}
