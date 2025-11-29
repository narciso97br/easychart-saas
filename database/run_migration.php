<?php

require_once __DIR__ . '/../app/core/Database.php';

// Exemplo de uso:
// /database/run_migration.php?class=CreateSpreadsheetsTable&dir=up
// /database/run_migration.php?class=CreateSpreadsheetsTable&dir=down

$class = $_GET['class'] ?? null;
$dir   = $_GET['dir'] ?? 'up';

if (!$class) {
    echo 'Informe ?class=NomeDaClasseDaMigration&dir=up|down';
    exit;
}

// Carrega todas as migrations disponíveis
foreach (glob(__DIR__ . '/migrations/*.php') as $file) {
    require_once $file;
}

if (!class_exists($class)) {
    echo 'Class ' . htmlspecialchars($class) . ' not found. Verifique o nome da classe na migration.';
    exit;
}

if (!in_array($dir, ['up', 'down'], true)) {
    echo 'Invalid dir. Use up or down.';
    exit;
}

call_user_func([$class, $dir]);

echo 'Migration ' . htmlspecialchars($class) . ' executed with dir=' . htmlspecialchars($dir);
