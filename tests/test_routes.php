<?php

use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;

// root route
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'Chat API is running']));
    return $response->withHeader('Content-Type', 'application/json');
});

// user routes
$app->post('/users', [UserController::class, 'create']);
$app->get('/users/{id}', [UserController::class, 'get']);

// group routes
$app->post('/groups', [GroupController::class, 'create']);
$app->post('/groups/{id}/join', [GroupController::class, 'join']);
$app->get('/groups/{id}/members', [GroupController::class, 'getMembers']);

// message routes
$app->post('/groups/{id}/messages', [MessageController::class, 'create']);
$app->get('/groups/{id}/messages', [MessageController::class, 'getByGroup']);
$app->get('/groups/{id}/messages/since/{timestamp}', [MessageController::class, 'getNewMessages']); 