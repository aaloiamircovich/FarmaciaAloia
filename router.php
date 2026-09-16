<?php
// Router para el servidor PHP embebido usado en Railway.
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Nunca exponer recetas, .env, archivos de configuración o SQL directamente.
$blockedPrefixes = ['/uploads/', '/config/', '/db/', '/scripts/', '/vendor/'];
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($uriPath, $prefix)) {
        http_response_code(404);
        exit('Not Found');
    }
}

$blockedFiles = ['/.env', '/.env.example', '/composer.json', '/composer.lock', '/railway.json', '/Dockerfile'];
if (in_array($uriPath, $blockedFiles, true)) {
    http_response_code(404);
    exit('Not Found');
}

if ($uriPath === '/health' || $uriPath === '/health.php') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok']);
    return true;
}

// Dejar que el servidor embebido resuelva archivos existentes (PHP y estáticos).
$path = __DIR__ . $uriPath;
if ($uriPath !== '/' && is_file($path)) {
    return false;
}

if ($uriPath === '/') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo '404 - Página no encontrada';
return true;
