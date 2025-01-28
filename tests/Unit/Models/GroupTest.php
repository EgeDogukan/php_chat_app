<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use PDO;
use App\Models\Group;
use Tests\TestDatabaseInitializer;

class GroupTest extends TestCase
{
    private $db;
    private $group;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $initializer = new TestDatabaseInitializer($this->db);
        $initializer->initialize();
        
        $this->group = new Group($this->db);
    }

    public function testCreateGroup(): void
    {
        $result = $this->group->create('test_group');
        
        $this->assertIsArray($result);
        $this->assertEquals('test_group', $result['name']);
    }

    public function testGetGroupById(): void
    {
        $created = $this->group->create('test_group');
        $result = $this->group->getById($created['id']);
        
        $this->assertIsArray($result);
        $this->assertEquals('test_group', $result['name']);
    }

    public function testGetNonExistentGroup(): void
    {
        $result = $this->group->getById(999);
        $this->assertFalse($result);
    }

    public function testCreateGroupWithEmptyName(): void
    {
        $result = $this->group->create('');
        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
    }
} 