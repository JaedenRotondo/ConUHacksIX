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
        'model' => 'gpt-4o-mini',
        'messages'   => [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "$query, describe the image thoroughly.",
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
define("WHATSAPP_TOKEN", "EAA7LhWBJQbYBOyyRNERwVSUGYImPb6g09eD7DHZCn9KMHAR5lBZABt5F310CZCdGvKwpEuRqcbpyYsV2eek1OTxHGeV0RW8xqxZA8F4mQleXKRrKT4Yijnzruz0Tp6IFwSx6XURrOHWfPEF7o1Bamk5rhx71ITNSVcjlZCtn4f1HDWRvxHvQGrSe7V4ZAg8OdKZCgvjZBS1AZA4oIINtPpdkHnZAaZCwWyGigfq94I6ZAPahPYwZD");
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