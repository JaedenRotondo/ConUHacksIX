<?php
require 'vendor/autoload.php';
require 'vendor/autoload.php';
require_once 'vonage.php';

// Meta Glass send a message to whats app business account
// Whats app account send a message + image to the server
// Server send query to chat gpt with the custom prompt
// Get the answer back and call someone what's app phone

function getGPTResponse($query)
{
    $client = OpenAI::client(OPENAI_KEY);
    $data = $client->chat()->create([
        'model' => 'gpt-3.5-turbo',
        'messages' => [[
            'role' => 'user',
            'content' => "Limit your response to 100 characters for this query: $query",
        ]],
    ]);

    return $data['choices'][0]['message']['content'];
}

function getGPTImageResponse($query = "What’s in this image? ")
{
    $base64Image = encodeImage(SAVE_IMAGE_PATH);
    $client = OpenAI::client(OPENAI_KEY);
    $data = $client->chat()->create([
        'model'      => 'gpt-4-vision-preview',
        'messages'   => [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "$query, limit your response to 100 characters or less."
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => "data:image/jpeg;base64,$base64Image"
                    ],
                ],
            ]
        ],
        'max_tokens' => 200,
    ]);

    return $data['choices'][0]['message']['content'];
}

function sendWhatsappResponse($response)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://graph.facebook.com/v19.0/' . WHATSAPP_SENDER_ID . '/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{"messaging_product": "whatsapp", "to": "' . WHATSAPP_INCOMING_PHONE_NUMBER . '","text": {"body" : "' . $response . '"}}',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . WHATSAPP_TOKEN,
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
}
function getMediaLink($mediaID)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://graph.facebook.com/v19.0/' . $mediaID,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . WHATSAPP_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response)->url;
}

function downloadMediaLink($url)
{
    $curl = curl_init();
    $fp = fopen(SAVE_IMAGE_PATH, 'w+');
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_FILE => $fp,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_USERAGENT => 'PostmanRuntime/7.36.0',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . WHATSAPP_TOKEN
        ),
    ));

    curl_exec($curl);
    curl_close($curl);
    fclose($fp);
}

function encodeImage($imagePath): string
{
    $imageContent = file_get_contents($imagePath);
    return base64_encode($imageContent);
}

// CHANGE THESE!
define("WHATSAPP_TOKEN", "EAA7LhWBJQbYBO9C7PzwjB4oxwYEZAeRTtG9TSCpjZC4FO5RJOkmINxc0DC6kW1xAkc5g4OwOmjyZBZCjtUA73bZA6oOWRCbC1cX0IcNOCrRNk27p7K4pfQcRL1z4RZAA3s8TEfgZAdwWhyjIRf2YZAWu0RUkWsqlf0ZBOe1DkKphWPOXVkahbPgzOEZAeB85iLaQkV0ZAIRLmeBZA0m3SeNdha6VZBaZBzZBxI6BfZAMjB4yHS4m4DIZD");
define("WHATSAPP_SENDER_ID", "568530893007468");
define("WHATSAPP_INCOMING_PHONE_NUMBER", "+15551532961");
define("OPENAI_KEY", "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");

define("SAVE_IMAGE_PATH", "query_image.jpg");

if (isset($_GET['hub_challenge'])) {
    // Used for verification of Whatsapp
    echo ($_GET['hub_challenge']);
} else if (isset($_GET['trigger_twilio_call']) && $_GET['trigger_twilio_call'] == 'yes'){
    try {
        $message = <<<EOT
 I need immediate police and medical assistance!
I’m at 123 Main Street, Apartment 5B!
A man with a knife is attacking people in the park!
There are two victims injured!
The fire is spreading fast, and I’m stuck on the second floor!
The suspect is a tall man wearing a black hoodie and jeans, carrying a handgun!
One person is unconscious and not breathing!
EOT;
        initiateVonageCall('+15148161120', $message);
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    $json = file_get_contents('php://input');

    // Uncomment the following lines if you need to read the incoming data from the Whastapp webhook
    file_put_contents("debug.txt", $json);
    die();

    $json = json_decode($json);

    $message = $json->entry[0]->changes[0]->value->messages[0];
    if ($message->from == WHATSAPP_INCOMING_PHONE_NUMBER) {
        if ($message->type == "text") {
            $query = $message->text->body;
            if (file_exists(SAVE_IMAGE_PATH)) {
                $response = getGPTImageResponse($query);
                unlink(SAVE_IMAGE_PATH);
            } else {
                $response = getGPTResponse($query);
            }
            sendWhatsappResponse($response);
        } else if ($message->type == "image") {
            $mediaLink = getMediaLink($message->image->id);
            downloadMediaLink($mediaLink);
        }
    }
}