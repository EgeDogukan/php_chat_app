<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use PDO;
use App\Models\User;
use Tests\TestDatabaseInitializer;

class UserTest extends TestCase
{
    private $db;
    private $user;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $initializer = new TestDatabaseInitializer($this->db);
        $initializer->initialize();
        
        $this->user = new User($this->db);
    }

    public function testCreateUser(): void
    {
        $result = $this->user->create('test_user');
        
        $this->assertIsArray($result);
        $this->assertEquals('test_user', $result['username']);
    }

    public function testCreateDuplicateUser(): void
    {
        $this->user->create('test_user');
        $result = $this->user->create('test_user');
        
        $this->assertFalse($result);
    }

    public function testGetUserById(): void
    {
        $created = $this->user->create('test_user');
        $result = $this->user->getById($created['id']);
        
        $this->assertIsArray($result);
        $this->assertEquals('test_user', $result['username']);
    }

    public function testGetNonExistentUser(): void
    {
        $result = $this->user->getById(999);
        $this->assertFalse($result);
    }

    public function testCreateUserWithEmptyUsername(): void
    {
        $result = $this->user->create('');
        $this->assertIsArray($result);
        $this->assertEquals('', $result['username']);
    }
} 