<?php
require 'vendor/autoload.php';
require 'vonage.php';

function getGPTResponse($query)
{
    $client = OpenAI::client(OPENAI_KEY);
    $data = $client->chat()->create([
        'model' => 'gpt-3.5-turbo',
        'messages' => [[
            'role' => 'user',
            'content' => "You are aiding a call operator for the police. Take this voice message from a caller and summarize it for the call operator and include any peritnent information: $query",
        ]],
    ]);

    return $data['choices'][0]['message']['content'];
}

function getGPTImageResponse($query = "You are aiding a call operator for the police. Take this image and describe it thoroughly to help police find suspects and nearby surroundings.")
{
    $base64Image = encodeImage(SAVE_IMAGE_PATH);
    $client = OpenAI::client(OPENAI_KEY);
    $data = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages'   => [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "You are aiding a call operator for the police. Take this image and describe it thoroughly to help police find suspects and nearby surroundings.",
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:image/jpeg;base64,$base64Image"
                        ],
                    ],
                ],
            ]
        ],
        'max_tokens' => 200,
    ]);

    echo "DEBUG: ";    

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
    echo $response;
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
define("WHATSAPP_TOKEN", "EAA7LhWBJQbYBO3gjFGAgECiCREyRLGnVg7Gvd8MKrA7ZCGQzYfXF8JekiKP2OZAfyxhmekvOQugKb5HDXv8RpqDDRNFrgZA4GU4CrXg6vC7dFy9KrCDbJlrD3XtGXn2BiSMs1ZC7UomXwZAjOJUw5gMZBJmZAmXsSq5oT6NBNZACUyRLmVUpXeQX8HFFJGtT7iizLb9YAMXvXB2LTrBZBslUpgRlOoAKrLGAAO4Xex1lUu5MZD");
define("WHATSAPP_SENDER_ID", "568530893007468");
define("WHATSAPP_INCOMING_PHONE_NUMBER", "+15551532961");
define("OPENAI_KEY", "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");

define("SAVE_IMAGE_PATH", "query_image.jpg");

if (isset($_GET['hub_challenge'])) {
    // Used for verification of Whatsapp
    echo ($_GET['hub_challenge']);
} else {
    $json = file_get_contents('php://input');

    // Uncomment the following lines if you need to read the incoming data from the Whastapp webhook
    //file_put_contents("debug.txt", $json);
    //die();

    $json = json_decode($json);

    $message = $json->entry[0]->changes[0]->value->messages[0];
    if ($message->from == WHATSAPP_INCOMING_PHONE_NUMBER || true) {
        if ($message->type == "text") {
            print_r($message);
            $query = $message->text->body;
            if (file_exists(SAVE_IMAGE_PATH)) {
                $response = getGPTResponse($query);
                unlink(SAVE_IMAGE_PATH);
            } else {
                $response = getGPTResponse($query);
            }
            file_put_contents("debug.txt", $response);
            initiateVonageCall("+15148161120", $response);
            sendWhatsappResponse($response);
        } else if ($message->type == "image") {
            $mediaLink = getMediaLink($message->image->id);
            echo $mediaLink;
            downloadMediaLink($mediaLink);
            $response = getGPTImageResponse();
            file_put_contents("debug.txt", $response);
            initiateVonageCall("+15148161120", $response);
        }
    }
}