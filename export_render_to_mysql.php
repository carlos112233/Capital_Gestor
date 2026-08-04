<?php

$pgHost = 'dpg-d5skoje3jp1c738bn620-a.oregon-postgres.render.com';
$pgUser = 'admin';
$pgPass = 'on9URKhHQEpcZ1LCZucRtr7g3PVjAA2k';
$pgDb   = 'gestor_capital_db';
$pgPort = 5432;

echo "Conectando a Render PostgreSQL...\n";

try {
    $dsn = "pgsql:host=$pgHost;port=$pgPort;dbname=$pgDb;sslmode=require";
    $pdo = new PDO($dsn, $pgUser, $pgPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Error al conectar a PostgreSQL: " . $e->getMessage() . "\n");
}

echo "Conexión exitosa. Extrayendo datos...\n";

$tables = [
    'users',
    'roles',
    'role_user',
    'articulos',
    'pedidos',
    'ventas',
    'entradas',
    'whatsapp_pending_messages',
    'migrations',
    'personal_access_tokens',
    'password_reset_tokens'
];

$outputFile = __DIR__ . '/render_mysql_dump.sql';
$handle = fopen($outputFile, 'w');

fwrite($handle, "-- Backup adaptado para MySQL generado automaticamente\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM \"$table\"");
        $rows = $stmt->fetchAll();
        
        if (empty($rows)) {
            echo "Tabla '$table' está vacía. Saltando...\n";
            continue;
        }

        echo "Exportando tabla '$table' (" . count($rows) . " registros)...\n";

        $columns = array_keys($rows[0]);
        $escapedColumns = array_map(fn($col) => "`$col`", $columns);
        $colList = implode(', ', $escapedColumns);

        fwrite($handle, "-- Registros de la tabla `$table` --\n");
        fwrite($handle, "DELETE FROM `$table`;\n");

        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } elseif (is_bool($val)) {
                    $values[] = $val ? '1' : '0';
                } else {
                    // Escape values for MySQL
                    $escaped = addslashes($val);
                    // Replace newlines safely
                    $escaped = str_replace("\r", "\\r", $escaped);
                    $escaped = str_replace("\n", "\\n", $escaped);
                    $values[] = "'$escaped'";
                }
            }
            $valList = implode(', ', $values);
            fwrite($handle, "INSERT INTO `$table` ($colList) VALUES ($valList);\n");
        }
        fwrite($handle, "\n");
    } catch (Exception $e) {
        echo "Aviso al procesar tabla '$table': " . $e->getMessage() . "\n";
    }
}

fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($handle);

echo "¡Completado! El archivo 'render_mysql_dump.sql' se ha generado exitosamente.\n";
