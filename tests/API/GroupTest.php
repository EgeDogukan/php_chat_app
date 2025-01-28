<?php

namespace Tests\API;

use Tests\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class GroupTest extends TestCase
{
    public function testCreateGroup(): void
    {
        $response = $this->sendRequest('POST', '/groups', [
            'name' => 'test_group'
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertEquals('test_group', $data['name']);
    }

    public function testCreateGroupWithoutName(): void
    {
        $response = $this->sendRequest('POST', '/groups', []);
        
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testJoinGroup(): void
    {
        $userResponse = $this->sendRequest('POST', '/users', ['username' => 'test_user']);
        $this->assertEquals(201, $userResponse->getStatusCode());
        $userData = json_decode((string) $userResponse->getBody(), true);
        $this->assertIsArray($userData);
        $this->assertArrayHasKey('id', $userData);

        $groupResponse = $this->sendRequest('POST', '/groups', ['name' => 'test_group']);
        $this->assertEquals(201, $groupResponse->getStatusCode());
        $groupData = json_decode((string) $groupResponse->getBody(), true);
        $this->assertIsArray($groupData);
        $this->assertArrayHasKey('id', $groupData);

        $joinResponse = $this->sendRequest('POST', "/groups/{$groupData['id']}/join", [
            'user_id' => $userData['id']
        ]);
        
        if ($joinResponse->getStatusCode() !== 200) {
            $error = json_decode((string) $joinResponse->getBody(), true);
            var_dump([
                'status' => $joinResponse->getStatusCode(),
                'error' => $error,
                'group_id' => $groupData['id'],
                'user_id' => $userData['id']
            ]);
        }
        
        $this->assertEquals(200, $joinResponse->getStatusCode());
    }

    public function testJoinNonExistentGroup(): void
    {
        $userResponse = $this->sendRequest('POST', '/users', ['username' => 'test_user']);
        $this->assertEquals(201, $userResponse->getStatusCode());
        $userData = json_decode((string) $userResponse->getBody(), true);
        $this->assertIsArray($userData);
        $this->assertArrayHasKey('id', $userData);

        $response = $this->sendRequest('POST', '/groups/999/join', [
            'user_id' => $userData['id']
        ]);
        
        $this->assertEquals(404, $response->getStatusCode());
    }
} 