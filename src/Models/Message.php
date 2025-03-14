<?php

namespace App\Models;

use App\Services\RedisService;

class Message
{
    private $db;
    private $redis;
    
    public function __construct(\PDO $db, RedisService $redis)
    {
        $this->db = $db;
        $this->redis = $redis;
    }
    
    // creates a new message in a group
    // returns message data if successful
    // returns 'group_not_found' if group doesn't exist
    // returns 'user_not_found' if user doesn't exist
    // returns 'not_member' if user is not a member of the group
    // returns false if database error
    public function create(int $groupId, int $userId, string $content): array|string|false
    {
        try {
            $this->db->beginTransaction();

            // group check
            $stmt = $this->db->prepare('SELECT id FROM groups WHERE id = :id');
            $stmt->execute(['id' => $groupId]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                return 'group_not_found';
            }

            // user check
            $stmt = $this->db->prepare('SELECT id FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                return 'user_not_found';
            }

            // member check
            $stmt = $this->db->prepare('
                SELECT 1 FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id
            ');
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                return 'not_member';
            }

            // create message
            $stmt = $this->db->prepare('
                INSERT INTO messages (group_id, user_id, content)
                VALUES (:group_id, :user_id, :content)
            ');
            
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId,
                'content' => $content
            ]);

            $messageId = $this->db->lastInsertId();
            $this->db->commit();

            $this->redis->set("group:{$groupId}:messages", null);

            return [
                'id' => $messageId,
                'group_id' => $groupId,
                'user_id' => $userId,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ];

        } catch (\PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // returns messages from a group
    // returns array of messages if group exists and user is a member
    // returns 'not_member' if user is not a member
    // returns null if group doesn't exist
    // returns false on error
    public function getByGroupId(int $groupId, int $userId): array|string|null|false
    {
        try {
            // check if group exists and user is a member
            $stmt = $this->db->prepare('
                SELECT 1 FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id
            ');
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);
            
            if (!$stmt->fetch()) {
                // check if group exists at all
                $stmt = $this->db->prepare('SELECT 1 FROM groups WHERE id = :id');
                $stmt->execute(['id' => $groupId]);
                return $stmt->fetch() ? 'not_member' : null;
            }

            // trying cache first if not normal db query
            $cachedMessages = $this->redis->getCachedGroupMessages($groupId);
            if ($cachedMessages !== null) {
                return $cachedMessages;
            }

            $stmt = $this->db->prepare('
                SELECT m.id, m.content, m.created_at,
                       u.id as user_id, u.username
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.group_id = :group_id
                ORDER BY m.created_at DESC
            ');
            
            $stmt->execute(['group_id' => $groupId]);
            $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // cache the messages
            $this->redis->cacheGroupMessages($groupId, $messages);

            return $messages;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // an optimized version of getByGroup that only returns messages newer than the specified timestamp
    public function getByGroupIdAfterTimestamp(int $groupId, int $userId, string $timestamp): array|string|null|false
    {
        try {
            // check if group exists and user is a member
            $stmt = $this->db->prepare('
                SELECT 1 FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id
            ');
            $stmt->execute([
                'group_id' => $groupId,
                'user_id' => $userId
            ]);
            
            if (!$stmt->fetch()) {
                // check if group exists at all
                $stmt = $this->db->prepare('SELECT 1 FROM groups WHERE id = :id');
                $stmt->execute(['id' => $groupId]);
                return $stmt->fetch() ? 'not_member' : null;
            }

            $stmt = $this->db->prepare('
                SELECT m.id, m.content, m.created_at,
                       u.id as user_id, u.username
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.group_id = :group_id
                AND m.created_at > :timestamp
                ORDER BY m.created_at DESC
            ');
            
            $stmt->execute([
                'group_id' => $groupId,
                'timestamp' => $timestamp
            ]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return false;
        }
    }
} 