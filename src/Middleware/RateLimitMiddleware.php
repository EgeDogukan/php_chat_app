<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\RedisService;
use Slim\Psr7\Response;

class RateLimitMiddleware
{
    private $redis;
    
    public function __construct(RedisService $redis)
    {
        $this->redis = $redis;
    }
    
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        
        // rate limit POST requests to /groups/{id}/messages
        if ($method === 'POST' && preg_match('/^\/groups\/\d+\/messages$/', $path)) {
            $data = $request->getParsedBody();
            $userId = $data['user_id'] ?? null;
            
            if ($userId && $this->redis->isRateLimited("message:user:{$userId}", 60, 60)) { // 60 messages per minute
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'error' => 'Too many messages. Please wait a moment before sending more.'
                ]));
                return $response
                    ->withStatus(429)
                    ->withHeader('Content-Type', 'application/json');
            }
        }
        
        return $handler->handle($request);
    }
} 