<?php
require __DIR__ . '/vendor/autoload.php'; // Ensure Twilio SDK is installed via Composer
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Constants
$TWILIO_ACCOUNT_SID = $_ENV['TWILIO_ACCOUNT_SID'];
$TWILIO_API_KEY = $_ENV['TWILIO_API_KEY'];
$TWILIO_API_SECRET = $_ENV['TWILIO_API_SECRET'];
$TWIML_APP_SID = $_ENV['TWIML_APP_SID'];
$TWILIO_PHONE_NUMBER = $_ENV['TWILIO_PHONE_NUMBER'];

// Generate a Twilio Access Token
if ($_SERVER['PATH_INFO'] === '/token' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $identity = isset($_GET['identity']) ? $_GET['identity'] : 'default_user';

    $voiceGrant = new VoiceGrant();
    $voiceGrant->setOutgoingApplicationSid($TWIML_APP_SID);
    $voiceGrant->setIncomingAllow(true); // Allow incoming calls

    $token = new AccessToken(
        $TWILIO_ACCOUNT_SID,
        $TWILIO_API_KEY,
        $TWILIO_API_SECRET,
        3600, // Token expiry in seconds (1 hour)
        $identity
    );

    $token->addGrant($voiceGrant);

    header('Content-Type: application/json');
    echo json_encode([
        'identity' => $identity,
        'token' => $token->toJWT()
    ]);
    exit;
}

if ($_SERVER['PATH_INFO'] === '/disconnect' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = new VoiceResponse();
    $response->reject();
    header('Content-Type: text/xml');
    echo $response;
    exit;
}

if ($_SERVER['PATH_INFO'] === '/voice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //$input = json_decode(file_get_contents('php://input'), true);
    //$to = isset($input['to']) ? $input['to'] : null;
    $to = isset($_POST['To']) ? trim($_POST['To']) : "919500311812"; // Default number if not provided
// Start our TwiML response
$response = new VoiceResponse();
if ($to) {
// Dial the number
$dial = $response->dial("", [
    'callerId' => $TWILIO_PHONE_NUMBER,
    'record' => 'record-from-answer' // Enable recording
    ]);
$dial->number($to);
}else{
    $response->say('Thanks for calling!');
}

header('Content-Type: text/xml');
echo $response;
exit;
}
http_response_code(404);
echo json_encode(['error' => 'Route not found']);