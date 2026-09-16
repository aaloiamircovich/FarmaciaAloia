<?php
require_once __DIR__ . '/../env.php';

class Database
{
    private static ?mysqli $instance = null;

    public static function getConnection(): mysqli
    {
        if (self::$instance === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                self::$instance = new mysqli(
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME,
                    (int)DB_PORT
                );
                self::$instance->set_charset('utf8mb4');
            } catch (mysqli_sql_exception $e) {
                $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

                if ($isApi) {
                    header('Content-Type: application/json; charset=utf-8', true, 500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No se pudo conectar con MySQL. Revisá que MySQL esté iniciado y que el archivo .env sea correcto.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                die('Error de conexión a MySQL. Revisá XAMPP y el archivo .env.');
            }
        }

        return self::$instance;
    }
}
