<?php
/**
 * Configuración general del proyecto Farmacia y Perfumería.
 * Funciona tanto en XAMPP (.env) como en Railway (variables de entorno).
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';

    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

function getEnvVal(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($value !== false && $value !== null && $value !== '') ? (string)$value : $default;
}

// XAMPP usa DB_*. Railway MySQL expone MYSQL*; aceptamos ambos.
define('DB_HOST', getEnvVal('DB_HOST', getEnvVal('MYSQLHOST', '127.0.0.1')));
define('DB_PORT', getEnvVal('DB_PORT', getEnvVal('MYSQLPORT', '3306')));
define('DB_NAME', getEnvVal('DB_NAME', getEnvVal('MYSQLDATABASE', 'farmacia_online')));
define('DB_USER', getEnvVal('DB_USER', getEnvVal('MYSQLUSER', 'root')));
define('DB_PASS', getEnvVal('DB_PASS', getEnvVal('MYSQLPASSWORD', '')));

define('MP_ACCESS_TOKEN', getEnvVal('MP_ACCESS_TOKEN', ''));
define('MP_PUBLIC_KEY', getEnvVal('MP_PUBLIC_KEY', ''));

// En Railway, RAILWAY_PUBLIC_DOMAIN se crea al generar un dominio público.
$rawBaseUrl = getEnvVal('BASE_URL');
if (!$rawBaseUrl) {
    $railwayDomain = getEnvVal('RAILWAY_PUBLIC_DOMAIN');
    if ($railwayDomain !== '') {
        $rawBaseUrl = 'https://' . $railwayDomain;
    } else {
        // Railway termina HTTPS en su proxy y lo informa en esta cabecera.
        $forwardedProtocol = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $forwardedProtocol === 'https';
        $protocol = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Detectar la carpeta real: en Railway es la raíz; en XAMPP puede ser una subcarpeta.
        $documentRoot = !empty($_SERVER['DOCUMENT_ROOT'])
            ? (realpath($_SERVER['DOCUMENT_ROOT']) ?: '')
            : '';
        $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $projectRoot = str_replace('\\', '/', __DIR__);
        $basePath = '';
        if ($documentRoot !== '' && str_starts_with($projectRoot, $documentRoot . '/')) {
            $basePath = substr($projectRoot, strlen($documentRoot));
        }

        $rawBaseUrl = $protocol . '://' . $host . $basePath;
    }
}

$cleanBaseUrl = trim($rawBaseUrl, "\"' \t\n\r\0\x0B/");
if (!preg_match('/^https?:\\/\\//i', $cleanBaseUrl)) {
    $cleanBaseUrl = 'http://' . $cleanBaseUrl;
}

define('BASE_URL', $cleanBaseUrl);
define('PRESCRIPTION_MAX_BYTES', 5 * 1024 * 1024); // 5 MB

/**
 * Directorio privado para recetas.
 * En Railway se recomienda montar un Volume en /data y usar
 * PRESCRIPTION_UPLOAD_DIR=/data/recetas.
 */
function getPrescriptionUploadDir(): string
{
    $configured = trim(getEnvVal('PRESCRIPTION_UPLOAD_DIR'));
    $dir = $configured !== '' ? $configured : (__DIR__ . '/uploads/recetas');

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $real = realpath($dir);
    return $real !== false ? $real : $dir;
}

function getSessionStorageDir(): ?string
{
    $configured = trim(getEnvVal('SESSION_SAVE_PATH'));
    if ($configured === '') {
        return null;
    }

    if (!is_dir($configured)) {
        @mkdir($configured, 0775, true);
    }

    return is_dir($configured) ? $configured : null;
}
