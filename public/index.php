<?php

use DI\Container;
use Slim\Factory\AppFactory;
use App\Models\User;
use App\Models\Group;
use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Models\Message;
use Dotenv\Dotenv;
use App\Services\RedisService;
use App\Middleware\RateLimitMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$container = new Container();

// set up container with db, models and controllers
$container->set('db', function() {
    $dbPath = dirname(__DIR__) . '/' . ($_ENV['DB_PATH'] ?? 'database/chat.sqlite');
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
});

// adding redis 
$container->set(RedisService::class, function() {
    return new RedisService(
        $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        (int) ($_ENV['REDIS_PORT'] ?? 6379),
        $_ENV['REDIS_PREFIX'] ?? 'chat:'
    );
});

// adding redis middleware
$container->set(RateLimitMiddleware::class, function($c) {
    return new RateLimitMiddleware($c->get(RedisService::class));
});

// models
$container->set(User::class, function($container) {
    return new User($container->get('db'));
});

$container->set(Group::class, function($container) {
    return new Group($container->get('db'));
});

$container->set(Message::class, function($container) {
    return new Message(
        $container->get('db'),
        $container->get(RedisService::class)
    );
});

// controllers
$container->set(UserController::class, function($container) {
    return new UserController($container->get(User::class));
});

$container->set(GroupController::class, function($container) {
    return new GroupController($container->get(Group::class));
});

$container->set(MessageController::class, function($container) {
    return new MessageController($container->get(Message::class));
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true); // adding error middleware for better debugging and better error handling
$app->addBodyParsingMiddleware();

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
$app->get('/groups/{id}/messages', [MessageController::class, 'getByGroup']);
$app->get('/groups/{id}/messages/since/{timestamp}', [MessageController::class, 'getNewMessages']);
$app->post('/groups/{id}/messages', [MessageController::class, 'create'])->add(RateLimitMiddleware::class);

$app->run(); 