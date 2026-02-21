<?php

$table = rex_request('table', 'string', '');

if ($table === '') {
    rex_response::sendJson([]);
    exit;
}

$sql = rex_sql::factory();
$columns = [];

try {
    $result = $sql->getArray('SHOW COLUMNS FROM ' . $sql->escapeIdentifier($table));
    foreach ($result as $row) {
        $columns[] = $row['Field'];
    }
} catch (rex_sql_exception $e) {
    // Table might not exist
}

rex_response::sendJson($columns);
exit;
