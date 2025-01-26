<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Database\DatabaseInitializer;

// create an instance of the db initializer then try to initialize the tables

try {
    $initializer = new DatabaseInitializer();
    $initializer->initializeTables();
    echo "Database tables created successfully!\n";
} catch (\PDOException $e) {
    echo "Error creating database tables: " . $e->getMessage() . "\n";
} 