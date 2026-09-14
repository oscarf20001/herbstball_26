<?php
// --- Header setzen ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// --- DB-Verbindung einbinden ---
require 'db_connection.php';

// --- JSON-Daten empfangen ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// --- IP-Adresse ermitteln ---
$ip = $_SERVER['HTTP_CLIENT_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR'];

// Für lokalen Test ersetze hier temporär:
//$ip = "2003:e9:4f1f:8000:c523:82f5:5341:5ece";

// --- Geo-API ipinfo.io aufrufen ---
function fetchGeoDataIpInfo($ip) {
    $token = '0b262b97b643b3'; // Optional: Registriere dich und ersetze hier deinen Token
    $url = "https://ipinfo.io/{$ip}/json";
    if ($token) {
        $url .= "?token={$token}";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false, // lokal ok, im Prod besser true setzen
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if (!$response) {
        error_log("cURL Fehler: $error");
        return null;
    }

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        error_log("ipinfo.io Fehler: " . json_encode($data['error']));
        return null;
    }

    return $data;
}

$geo = fetchGeoDataIpInfo($ip);
file_put_contents(__DIR__ . "/ipinfo_debug.log", "IP: $ip\nResponse:\n" . print_r($geo, true) . "\n\n", FILE_APPEND);

// --- MySQLi-Verbindung aufbauen ---
$mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbDatabase);
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "DB-Verbindung fehlgeschlagen: " . $mysqli->connect_error
    ]);
    exit;
}

// --- SQL-Vorbereitung ---
$sql = "
    INSERT INTO analytics (
        timestamp, device_type, user_agent, platform,
        language, timezone, screen_width, screen_height, pixel_ratio,
        referrer, ip, city, region, country, org,
        latitude, longitude, in_eu
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Prepare fehlgeschlagen: " . $mysqli->error]);
    exit;
}

// --- Helferfunktionen zur Typ-Sicherheit ---
function safeString($val) {
    return isset($val) && $val !== '' ? $val : null;
}
function safeInt($val) {
    return is_numeric($val) ? (int)$val : null;
}
function safeFloat($val) {
    return is_numeric($val) ? (float)$val : null;
}

// --- Daten aus JSON & Geo aufbereiten ---
$timestamp     = safeString($data['timestamp'] ?? null);
$deviceType    = safeString($data['deviceType'] ?? null);
$userAgent     = safeString($data['userAgent'] ?? null);
$platform      = safeString($data['platform'] ?? null);
$language      = safeString($data['language'] ?? null);
$timezone     = safeString($geo['timezone'] ?? ($data['timeZone'] ?? null));
$screenWidth   = safeInt($data['screen']['width'] ?? null);
$screenHeight  = safeInt($data['screen']['height'] ?? null);
$pixelRatio    = safeFloat($data['screen']['pixelRatio'] ?? null);
$referrer      = safeString($data['referrer'] ?? null);

$geo_ip        = safeString($geo['ip'] ?? $ip);
$city          = safeString($geo['city'] ?? null);
$region        = safeString($geo['region'] ?? null);
$country       = safeString($geo['country'] ?? null);
$org           = safeString($geo['org'] ?? null);

if (isset($geo['loc'])) {
    $loc = explode(',', $geo['loc']);
    $latitude  = safeFloat($loc[0] ?? null);
    $longitude = safeFloat($loc[1] ?? null);
} else {
    $latitude = null;
    $longitude = null;
}

$inEU = null; // ipinfo liefert kein in_eu, kann man ggf. ergänzen mit extra Logik

// --- Parameter binden ---
$stmt->bind_param(
    "sssssssiiisssssddi",
    $timestamp, $deviceType, $userAgent, $platform,
    $language, $timezone, $screenWidth, $screenHeight, $pixelRatio,
    $referrer, $geo_ip, $city, $region, $country, $org,
    $latitude, $longitude, $inEU
);

// --- Ausführen & Antwort ---
if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "SQL-Fehler: " . $stmt->error]);
}

// --- Aufräumen ---
$stmt->close();
$mysqli->close();
