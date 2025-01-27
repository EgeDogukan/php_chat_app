<?php

namespace App\Models;

class User
{
    private $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;    
    }
    
    // creates a new user
    // returns user data if successful, false if username already exists or other database error
    public function create(string $username)
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO users (username)
                VALUES (:username)
            ');
            
            // using prepared statement and binding the username to the query to prevent sql injections

            $stmt->execute(['username' => $username]);  
            
            return [
                'id' => $this->db->lastInsertId(),
                'username' => $username,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (\PDOException $e) {
            // username already exists or other database error
            return false;
        }
    }
    
    // returns user data or null if not found
    public function getById(int $id)
    {
        $stmt = $this->db->prepare('
            SELECT id, username, created_at
            FROM users
            WHERE id = :id
        '); 

        // using prepared statement and binding the id to the query to prevent sql injections

        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
} 