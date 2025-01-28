<?php

namespace App\Controllers;

use App\Models\Group;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GroupController
{
    private $groupModel;

    public function __construct(Group $groupModel)
    {
        $this->groupModel = $groupModel;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        if (!isset($data['name']) || trim($data['name']) === '') {
            $response->getBody()->write(json_encode(['error' => 'Group name is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $group = $this->groupModel->create($data['name']);
        
        if (!$group) {
            $response->getBody()->write(json_encode(['error' => 'Failed to create group']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($group));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function join(Request $request, Response $response, array $args): Response
    {
        $groupId = (int) $args['id'];
        $data = $request->getParsedBody();
        
        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            $response->getBody()->write(json_encode(['error' => 'Valid user_id is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->groupModel->addMember($groupId, (int) $data['user_id']);
        
        if ($result === 'group_not_found') {
            $response->getBody()->write(json_encode(['error' => 'Group not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($result === 'user_not_found') {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($result === 'already_member') {
            $response->getBody()->write(json_encode(['error' => 'User is already a member of this group']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        
        if ($result === false) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['message' => 'Successfully joined group']));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    public function getMembers(Request $request, Response $response, array $args): Response
    {
        $members = $this->groupModel->getMembers((int) $args['id']);
        
        if ($members === null) {
            $response->getBody()->write(json_encode(['error' => 'Group not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($members === false) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($members));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 