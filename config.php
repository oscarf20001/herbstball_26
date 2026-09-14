<?php

// config.php liegt direkt im Projektroot.
define('BASE_PATH', __DIR__);

// URL-Basis:
// Lokal z.B. /Metis/herbstball_25
// Produktion z.B. leer, wenn das Projekt direkt im Webroot liegt.

$isLocalhost =
    isset($_SERVER['HTTP_HOST']) &&
    (
        str_contains($_SERVER['HTTP_HOST'], 'localhost') ||
        str_contains($_SERVER['HTTP_HOST'], '127.0.0.1')
    );

if ($isLocalhost) {
    define('BASE_URL', '/Metis/herbstball_25');
} else {
    define('BASE_URL', '');
}