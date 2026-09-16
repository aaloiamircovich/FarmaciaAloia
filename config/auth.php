<?php
require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/session.php';

ensureAppSession();

function isAdminAuthenticated(): bool
{
    return !empty($_SESSION['admin_user']) && is_array($_SESSION['admin_user']);
}

function requireAdminAuth(): void
{
    if (isAdminAuthenticated()) {
        return;
    }

    $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Acceso denegado. Iniciá sesión como administrador.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}
