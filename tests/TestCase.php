<?php

namespace Tests;

use DI\Container;
use Slim\App;
use Slim\Factory\AppFactory;
use PDO;
use Tests\TestDatabaseInitializer;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use App\Controllers\UserController;
use App\Controllers\GroupController;
use App\Controllers\MessageController;

class TestCase extends \PHPUnit\Framework\TestCase
{
    private static $db;
    
    protected function getDatabase(): PDO
    {
        if (!self::$db) {
            self::$db = new PDO('sqlite::memory:');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $initializer = new TestDatabaseInitializer(self::$db);
            $initializer->initialize();
        }
        return self::$db;
    }

    protected function createApplication(): App
    {
        $container = new Container();
        
        $container->set('db', function() {
            return $this->getDatabase();
        });
        
        // set up models
        $container->set(User::class, function($c) {
            return new User($c->get('db'));
        });
        
        $container->set(Group::class, function($c) {
            return new Group($c->get('db'));
        });
        
        $container->set(Message::class, function($c) {
            return new Message($c->get('db'));
        });
        
        // set up controllers
        $container->set(UserController::class, function($c) {
            return new UserController($c->get(User::class));
        });
        
        $container->set(GroupController::class, function($c) {
            return new GroupController($c->get(Group::class));
        });
        
        $container->set(MessageController::class, function($c) {
            return new MessageController($c->get(Message::class));
        });
        
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        
        $app->addBodyParsingMiddleware();
        
        // add routes
        require __DIR__ . '/test_routes.php';
        
        return $app;
    }

    protected function createRequest(
        string $method,
        string $path,
        array $body = null
    ): \Psr\Http\Message\ServerRequestInterface {
        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())
            ->createServerRequest($method, $path);

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($body);
        }

        return $request;
    }

    protected function sendRequest(
        string $method,
        string $path,
        array $body = null
    ): \Psr\Http\Message\ResponseInterface {
        $request = $this->createRequest($method, $path, $body);
        return $this->createApplication()->handle($request);
    }

    protected function setUp(): void
    {
        // clear database tables before each test
        $db = $this->getDatabase();
        $db->exec('DELETE FROM messages');
        $db->exec('DELETE FROM group_members');
        $db->exec('DELETE FROM groups');
        $db->exec('DELETE FROM users');
    }
} 