<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;
use App\Models\Group;
use App\Models\User;
use Tests\TestDatabaseInitializer;

class GroupTest extends TestCase
{
    private $db;
    private $group;
    private $user;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $initializer = new TestDatabaseInitializer($this->db);
        $initializer->initialize();
        
        $this->group = new Group($this->db);
        $this->user = new User($this->db);
    }

    public function testAddMemberToGroup(): void
    {
        $user = $this->user->create('test_user');
        $group = $this->group->create('test_group');
        
        $result = $this->group->addMember($group['id'], $user['id']);
        
        $this->assertTrue($result);
    }

    public function testAddNonExistentUserToGroup(): void
    {
        $group = $this->group->create('test_group');
        
        $result = $this->group->addMember($group['id'], 999);
        
        $this->assertEquals('user_not_found', $result);
    }

    public function testAddUserToNonExistentGroup(): void
    {
        $user = $this->user->create('test_user');
        
        $result = $this->group->addMember(999, $user['id']);
        
        $this->assertEquals('group_not_found', $result);
    }

    public function testGetGroupMembers(): void
    {
        $user1 = $this->user->create('user1');
        $user2 = $this->user->create('user2');
        $group = $this->group->create('test_group');
        
        $this->group->addMember($group['id'], $user1['id']);
        $this->group->addMember($group['id'], $user2['id']);
        
        $members = $this->group->getMembers($group['id']);
        
        $this->assertIsArray($members);
        $this->assertCount(2, $members);
    }
} 