<?php

namespace App\Services;

use Predis\Client;

class RedisService
{
    private $redis;
    private $prefix;
    
    public function __construct(string $host = '127.0.0.1', int $port = 6379, string $prefix = 'chat:')
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => $host,
            'port'   => $port,
        ]);
        $this->prefix = $prefix;
    }
    
    public function get(string $key)
    {
        $value = $this->redis->get($this->prefix . $key);
        return $value ? json_decode($value, true) : null;
    }
    
    public function set(string $key, $value, int $ttl = 3600): void
    {
        $this->redis->setex(
            $this->prefix . $key,
            $ttl,
            json_encode($value)
        );
    }
    
    public function isRateLimited(string $key, int $maxAttempts = 60, int $decaySeconds = 60): bool
    {
        $attempts = $this->redis->incr($this->prefix . 'ratelimit:' . $key);
        
        if ($attempts === 1) {
            $this->redis->expire($this->prefix . 'ratelimit:' . $key, $decaySeconds);
        }
        
        return $attempts > $maxAttempts;
    }
    
    public function cacheGroupMessages(int $groupId, array $messages): void
    {
        $this->set("group:{$groupId}:messages", $messages, 300); // 5 min
    }
    
    public function getCachedGroupMessages(int $groupId): ?array
    {
        return $this->get("group:{$groupId}:messages");
    }
} 