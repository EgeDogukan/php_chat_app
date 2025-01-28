<?php

namespace App\Controllers;

use App\Models\Message;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessageController
{
    private $messageModel;

    public function __construct(Message $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        $groupId = (int) $args['id'];
        $data = $request->getParsedBody();
        
        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            $response->getBody()->write(json_encode(['error' => 'Valid user_id is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!isset($data['content']) || trim($data['content']) === '') {
            $response->getBody()->write(json_encode(['error' => 'Message content is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->messageModel->create(
            $groupId,
            (int) $data['user_id'],
            trim($data['content'])
        );

        if ($result === 'group_not_found') {
            $response->getBody()->write(json_encode(['error' => 'Group not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($result === 'user_not_found') {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($result === 'not_member') {
            $response->getBody()->write(json_encode(['error' => 'User is not a member of this group']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        if ($result === false) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function getByGroup(Request $request, Response $response, array $args): Response
    {
        $messages = $this->messageModel->getByGroupId((int) $args['id']);

        if ($messages === null) {
            $response->getBody()->write(json_encode(['error' => 'Group not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($messages === false) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($messages));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // an optimized version of getByGroup that only returns messages newer than the specified timestamp
    public function getNewMessages(Request $request, Response $response, array $args): Response
    {
        $timestamp = urldecode($args['timestamp']);
        
        // we don't want to query the database if the timestamp is in the future
        try {
            $date = new \DateTime($timestamp);
            $now = new \DateTime();
            
            if ($date > $now) {
                $response->getBody()->write(json_encode(['error' => 'Timestamp cannot be in the future']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid timestamp format']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $messages = $this->messageModel->getByGroupIdAfterTimestamp(
            (int) $args['id'],
            $timestamp
        );

        if ($messages === null) {
            $response->getBody()->write(json_encode(['error' => 'Group not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($messages === false) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($messages));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 