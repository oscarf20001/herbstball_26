<?php
function getOutputURLEnding(){
    // Protokoll (http oder https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

    // Hostname (Domainname)
    $host = $_SERVER['HTTP_HOST'];

    // Aktueller URI-Pfad (z.B. /seite/1)
    $requestUri = $_SERVER['REQUEST_URI'];

    // Zusammensetzen der vollständigen URL
    $currentUrl = $protocol . '://' . $host . $requestUri;

    // Wenn die URL keinen Pfad nach dem Domainnamen enthält
    if (parse_url($currentUrl, PHP_URL_PATH) === '/' || parse_url($currentUrl, PHP_URL_PATH) === '') {
        return "index";
    } else {
        // Regex, um den gewünschten Teil des Strings zu extrahieren
        preg_match('#/([^/]+)\.([^/]+)$#', $currentUrl, $matches);
        // Der gewünschte Teil ist im ersten Capturing-Group (matches[1])
        $extractedPart = $matches[1] ?? "index"; // Default-Wert, falls nichts gefunden wird
        return $extractedPart;
    }
}
?>