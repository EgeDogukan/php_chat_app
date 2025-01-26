<?php

namespace App\Database;

use Dotenv\Dotenv;

class DatabaseInitializer
{
    private $db;

    public function __construct()
    {
        // get db path from env variables   
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();
        $dbPath = dirname(__DIR__, 2) . '/' . ($_ENV['DB_PATH']);        
        
        $this->db = new \PDO('sqlite:' . $dbPath); // connect / create sqlite db
        $this->db->exec('PRAGMA foreign_keys = ON'); // enabling foreign keys
    }

    public function initializeTables()
    {
        // stores user information
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // stores chat groups
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS groups (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // links users to groups they are in
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS group_members (
                group_id INTEGER,
                user_id INTEGER,
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (group_id, user_id),
                FOREIGN KEY (group_id) REFERENCES groups(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // stores messages
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER,
                user_id INTEGER,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES groups(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }
} 