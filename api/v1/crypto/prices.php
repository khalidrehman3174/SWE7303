<?php
header('Content-Type: application/json');

$url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether,ripple,solana,binancecoin&vs_currencies=gbp";

$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode([
        "error" => "Unable to fetch crypto prices"
    ]);
    exit;
}

echo $response;
