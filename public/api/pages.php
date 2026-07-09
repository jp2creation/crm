<?php

declare(strict_types=1);

$query = (string) ($_SERVER['QUERY_STRING'] ?? '');

$_SERVER['REQUEST_URI'] = '/api/pages'.($query !== '' ? "?{$query}" : '');
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__).'/index.php';

require dirname(__DIR__).'/index.php';
