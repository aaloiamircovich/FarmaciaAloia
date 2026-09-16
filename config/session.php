<?php
function ensureAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $sessionDir = function_exists('getSessionStorageDir') ? getSessionStorageDir() : null;
        if ($sessionDir) {
            session_save_path($sessionDir);
        }

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'httponly' => true,
                'secure' => str_starts_with((string)(defined('BASE_URL') ? BASE_URL : ''), 'https://'),
                'samesite' => 'Lax'
            ]);
        }
        session_start();
    }
}
