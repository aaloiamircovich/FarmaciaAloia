<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $schema = file_get_contents(__DIR__ . '/../db/railway_schema.sql');
    if ($schema === false) {
        throw new RuntimeException('No se pudo leer db/railway_schema.sql');
    }

    if (!$db->multi_query($schema)) {
        throw new RuntimeException($db->error);
    }

    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());

    if ($db->errno) {
        throw new RuntimeException($db->error);
    }

    fwrite(STDOUT, "Base de datos inicializada correctamente.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Error inicializando DB: {$e->getMessage()}\n");
    exit(1);
}
