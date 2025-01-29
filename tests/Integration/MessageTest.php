<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;
use App\Models\Message;
use App\Models\User;
use App\Models\Group;
use App\Services\RedisService;
use Tests\TestDatabaseInitializer;

class MessageTest extends TestCase
{
    private $db;
    private $message;
    private $user;
    private $group;
    private $redis;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $initializer = new TestDatabaseInitializer($this->db);
        $initializer->initialize();
        
        // init redis with test prefix to avoid conflicts with other tests
        $this->redis = new RedisService(
            '127.0.0.1',
            6379,
            'test:chat:'
        );
        
        $this->message = new Message($this->db, $this->redis);
        $this->user = new User($this->db);
        $this->group = new Group($this->db);
    }

    public function testSendMessageToGroup(): void
    {
        $user = $this->user->create('test_user');      
        $group = $this->group->create('test_group');       
        $this->group->addMember($group['id'], $user['id']);
        
        $result = $this->message->create(
            $group['id'],
            $user['id'],
            'Hello, world!'
        );
        
        $this->assertIsArray($result);
        $this->assertEquals('Hello, world!', $result['content']);
    }

    public function testSendMessageToNonExistentGroup(): void
    {
        $user = $this->user->create('test_user');
        
        $result = $this->message->create(999, $user['id'], 'Hello!');
        
        $this->assertEquals('group_not_found', $result);
    }

    public function testSendMessageAsNonExistentUser(): void
    {
        $group = $this->group->create('test_group');
        
        $result = $this->message->create($group['id'], 999, 'Hello!');
        
        $this->assertEquals('user_not_found', $result);
    }

    public function testSendMessageAsNonMember(): void
    {
        $user = $this->user->create('test_user');
        $group = $this->group->create('test_group');
        
        $result = $this->message->create($group['id'], $user['id'], 'Hello!');
        
        $this->assertEquals('not_member', $result);
    }

    public function testGetGroupMessages(): void
    {
        $user = $this->user->create('test_user');
        $group = $this->group->create('test_group');
        $this->group->addMember($group['id'], $user['id']);
        
        $this->message->create($group['id'], $user['id'], 'Message 1');
        $this->message->create($group['id'], $user['id'], 'Message 2');
        
        $messages = $this->message->getByGroupId($group['id'], $user['id']);
        
        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
    }
} 