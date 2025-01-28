<?php

// get port from environment variable or use 8000 as default
$port = $_ENV['APP_PORT'] ?? 8000;
$host = $_ENV['APP_HOST'] ?? 'localhost';

echo "Starting server at http://{$host}:{$port}\n";

// start PHP server
exec("php -S {$host}:{$port} -t public/"); 