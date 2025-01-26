<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new \DI\Container();   // DI container to inject dependencies easier into the app
AppFactory::setContainer($container);

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true); // adding error middleware for better debugging and better error handling

// simple route to check if the API is running
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'Chat API is running']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run(); 