<?php
header('Content-Type: application/json');

$url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether,ripple,solana,binancecoin&vs_currencies=gbp";

if (!function_exists('curl_init')) {
    echo json_encode([
        "error" => "cURL is not enabled in PHP"
    ]);
    exit;
}

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: FinPay/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false || !empty($curlError)) {
    echo json_encode([
        "error" => "cURL request failed",
        "details" => $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode([
        "error" => "Crypto API returned non-200 status",
        "status_code" => $httpCode,
        "body" => $response
    ]);
    exit;
}

echo $response;
