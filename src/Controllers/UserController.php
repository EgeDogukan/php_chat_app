<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // validate and create user
        
        if (!isset($data['username']) || trim($data['username']) === '') {
            $response->getBody()->write(json_encode(['error' => 'Username is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $user = $this->userModel->create($data['username']);
        
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Username already exists']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($user));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $user = $this->userModel->getById((int) $args['id']);
        
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($user));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 