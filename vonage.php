<?php
require_once __DIR__ . '/vendor/autoload.php';
use Vonage\Client;
use Vonage\Client\Credentials\Keypair;
use Vonage\Voice\OutboundCall;
use Vonage\Voice\Endpoint\Phone;
use Vonage\Voice\NCCO\NCCO;
use Vonage\Voice\NCCO\Action\Talk;

function initiateVonageCall($to_number, $message) {
    // ✅ Use your actual private key file and application ID
    $vonage_application_id = "141a62b6-c2a8-4db3-af1b-c89dbc7ac29e";  
    $private_key_path = __DIR__ . "/private.key";  // Correct way to provide a key file

    // ✅ Create Vonage Client with Keypair authentication
    $keypair = new Keypair(file_get_contents($private_key_path), $vonage_application_id);
    $client = new Client($keypair);

    // ✅ Set up the call
    $outboundCall = new OutboundCall(
        new Phone($to_number),  // Destination phone number
        new Phone("14382660191") // Your Vonage virtual number
    );

    // ✅ Create NCCO for voice message
    $ncco = new NCCO();
    $ncco->addAction(new Talk($message));
    $outboundCall->setNCCO($ncco);

    // ✅ Make the call
    try {
        $response = $client->voice()->createOutboundCall($outboundCall);
        return $response;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return null;
    }
}
?>