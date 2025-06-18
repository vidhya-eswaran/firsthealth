<?php

namespace App\Http\Controllers\Api;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        $client = $this->getGoogleClient();
        return redirect()->to($client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = $this->getGoogleClient();
        //dd($client);
        $code = $request->get('code');

        if ($code) {
            $token = $client->fetchAccessTokenWithAuthCode($code);

            // Store the token securely for future use
            session(['google_token' => $token]);
            return response()->json(['message' => 'Authenticated successfully.']);
        }

        return response()->json(['error' => 'Authentication failed.'], 401);
    }

    public function sendEmail(Request $request)
    {
        $client = $this->getGoogleClient();
        $token = session('google_token');

        if (!$token) {
            return response()->json(['error' => 'No authentication token found.'], 401);
        }

        $client->setAccessToken($token);

        $gmail = new Gmail($client);

        // Prepare the email
        $rawMessageString = "To: " . $request->to . "\r\n";
        $rawMessageString .= "Subject: " . $request->subject . "\r\n\r\n";
        $rawMessageString .= $request->message;
        $rawMessage = base64_encode($rawMessageString);

        $message = new Gmail\Message();
        $message->setRaw($rawMessage);

        try {
            $gmail->users_messages->send('me', $message);
            return response()->json(['message' => 'Email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getGoogleClient()
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));       
        

        $client->addScope(Gmail::GMAIL_SEND);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        return $client;
    }
    
    public function store(Request $request)
    {
        // Perform action
        $result = Model::create($request->all());
    
        // Log action
        Log::channel('action_log')->info('Data Stored', [
            'user_id' => auth()->id(),
            'data' => $request->all(),
            'result' => $result,
        ]);
    
        return response()->json($result);
    }
}

