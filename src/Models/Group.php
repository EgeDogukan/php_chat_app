<?php

namespace App\Models;

class Group
{
    private $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    // creates a new group
    // returns group data if successful, false if error
    public function create(string $name)
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO groups (name)
                VALUES (:name)
            ');
            
            $stmt->execute(['name' => $name]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'name' => $name,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (\PDOException $e) {
            return false;
        }
    }
    
    // returns group data or null if not found
    public function getById(int $id)
    {
        $stmt = $this->db->prepare('
            SELECT id, name, created_at
            FROM groups
            WHERE id = :id
        ');
        
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return null;
        }
    }

    // adds a user to a group
    // returns true if successful
    // returns 'group_not_found' if group doesn't exist
    // returns 'user_not_found' if user doesn't exist
    // returns 'already_member' if user is already in group
    // returns false if other database error
    public function addMember(int $groupId, int $userId): bool|string
    {
        try {
            $this->db->beginTransaction(); // use transaction to ensure atomicity since two operations are being performed

            // check if group exists
            $stmt = $this->db->prepare('SELECT id FROM groups WHERE id = :id');
            $stmt->execute(['id' => $groupId]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                return 'group_not_found';
            }

            // check if user exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                return 'user_not_found';
            }

            // add the member
            $stmt = $this->db->prepare('
                INSERT INTO group_members (group_id, user_id)
                VALUES (:group_id, :user_id)
            ');
            
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);

            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            $this->db->rollBack();
            if ($e->getCode() === '23000' || $e->getCode() === 19) {
                return 'already_member';
            }
            return false;
        }
    }

    // returns array of members if group exists, empty array if no members but group exists
    // returns null if group doesn't exist
    // returns false on error
    public function getMembers(int $groupId): ?array
    {
        try {
            $checkGroup = $this->getById($groupId);
            if (!$checkGroup) {
                return null; 
            }

            $stmt = $this->db->prepare('
                SELECT u.id, u.username, gm.joined_at
                FROM users u
                JOIN group_members gm ON u.id = gm.user_id
                WHERE gm.group_id = :group_id
            ');
            
            $stmt->execute(['group_id' => $groupId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return false;
        }
    }
} 