<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticketbestätigung des MCG 2025 - Powered by Metis</title>
    <link rel="stylesheet" href="/../../client/styles/barStyles.css">
    <link rel="stylesheet" href="/../../client/styles/form.css">
    <link rel="stylesheet" href="/../../client/styles/inputFields.css">
    <link rel="stylesheet" href="/../../client/styles/besteatigen.css">
    <script src="https://kit.fontawesome.com/b9446e8a7d.js" crossorigin="anonymous"></script>
</head>
<body>

<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require '../php/db_connection.php';

$id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!is_numeric($id) || !is_numeric($token)) {
    echo "<h1 style='color: red;'>❌ Ungültiger Link. Bitte überprüfe die URL.</h1>";
    exit;
}

// Vorab: Prüfen, ob überhaupt ein Käufer mit dieser ID existiert
$checkStmt = $conn->prepare("SELECT checked FROM kaeufer WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo "<h1 style='color: red;'>❌ Kein Käufer mit dieser ID gefunden.</h1>";
    exit;
}

$row = $checkResult->fetch_assoc();
$currentChecked = $row['checked'];
$checkStmt->close();

// Schon bestätigt?
if ($currentChecked === 1) {
    echo "<h1 style='color: orange;'>⚠️ Deine Tickets wurden bereits bestätigt!</h1>";
    exit;
}

// Code (token) stimmt nicht mit gespeichertem checked-Wert überein?
if ($currentChecked != $token) {
    echo "<h1 style='color: red;'>❌ Token ungültig. Der Bestätigungslink scheint falsch oder abgelaufen zu sein.</h1>";
    exit;
}

// Wenn alles stimmt: checked = 1 setzen
$updateStmt = $conn->prepare("UPDATE kaeufer SET checked = 1 WHERE id = ? AND checked = ?");
$updateStmt->bind_param("ii", $id, $token);

if (!$updateStmt->execute()) {
    echo "<h1 style='color: red;'>❌ Fehler beim Aktualisieren der Bestätigung. Bitte versuche es später erneut.</h1>";
    exit;
}

if ($updateStmt->affected_rows > 0) {
    echo "<h1 style='color: green;'>✅ Deine Tickets wurden erfolgreich bestätigt! Wir freuen uns auf dich 🎉</h1>";
} else {
    echo "<h1 style='color: red;'>❌ Die Bestätigung konnte nicht durchgeführt werden. Möglicherweise ist dein Link nicht mehr gültig.</h1>";
}

$updateStmt->close();
$conn->close();
