# Chat Application API Documentation

## Overview
A RESTful chat application backend built with PHP, Slim Framework, SQLite, and Redis. The application allows users to create chat groups, join them, and exchange messages.

## Technology Stack
- PHP 8.0+
- Slim Framework 4.0
- SQLite Database
- Redis (for caching and rate limiting)
- PHPUnit for testing

## Setup Instructions

### Prerequisites
- PHP 8.0 or higher
- Composer
- SQLite
- Redis server

### Installation
1. Clone the repository
2. Run `composer install`
3. Configure environment variables in `.env`or use default values:
4. Initialize database: `php src/Database/setup.php`
5. Make sure redis server is running otherwise send message endpoint will not work: `redis-server`
6. Start server: `php server.php`

## API Endpoints

### Users
#### Create User
- **POST** `/users`
- **Body**: `{"username": "john_doe"}`
- **Response**: 201 Created
  ```json
  {
    "id": 1,
    "username": "john_doe",
    "created_at": "2024-01-29 15:00:00"
  }
  ```

#### Get User
- **GET** `/users/{id}`
- **Response**: 200 OK
  ```json
  {
    "id": 1,
    "username": "john_doe",
    "created_at": "2024-01-29 15:00:00"
  }
  ```

### Groups
#### Create Group
- **POST** `/groups`
- **Body**: `{"name": "General Chat"}`
- **Response**: 201 Created
  ```json
  {
    "id": 1,
    "name": "General Chat",
    "created_at": "2024-01-29 15:00:00"
  }
  ```

#### Join Group
- **POST** `/groups/{group_id}/join`
- **Body**: `{"user_id": 1}`
- **Response**: 200 OK
  ```json
  {
    "message": "Successfully joined group"
  }
  ```

#### Get Group Members
- **GET** `/groups/{group_id}/members`
- **Response**: 200 OK
  ```json
  [
    {
      "id": 1,
      "username": "john_doe",
      "joined_at": "2024-01-29 15:00:00"
    }
  ]
  ```

### Messages
#### Send Message
- **POST** `/groups/{group_id}/messages`
- **Body**: `{"user_id": 1, "content": "Hello, everyone!"}`
- **Response**: 201 Created
- **Rate Limit**: 60 messages per minute per user enforced by redis
  ```json
  {
    "id": 1,
    "group_id": 1,
    "user_id": 1,
    "content": "Hello, everyone!",
    "created_at": "2024-01-29 15:00:00"
  }
  ```

#### Get Group Messages
- **GET** `/groups/{group_id}/messages?user_id=1`
- **Response**: 200 OK
  ```json
  [
    {
      "id": 1,
      "content": "Hello, everyone!",
      "created_at": "2024-01-29 15:00:00",
      "user_id": 1,
      "username": "john_doe"
    }
  ]
  ```

#### Get New Messages
- **GET** `/groups/{group_id}/messages/since/{timestamp}?user_id=1`
- **Response**: 200 OK
  ```json
  [
    {
      "id": 2,
      "content": "New message",
      "created_at": "2024-01-29 15:10:00",
      "user_id": 1,
      "username": "john_doe"
    }
  ]
  ```

## Error Responses
- 400 Bad Request: Invalid input
- 401 Unauthorized: Authentication required
- 403 Forbidden: Not a group member
- 404 Not Found: Resource doesn't exist
- 409 Conflict: Resource already exists
- 429 Too Many Requests: Rate limit exceeded
- 500 Internal Server Error: Server error

## Caching
- Message lists are cached in Redis for 5 minutes
- Cache is automatically invalidated when new messages are posted
- Rate limiting uses Redis for tracking message counts

## Testing
1. Install dependencies: `composer install`
2. Run tests: `./vendor/bin/phpunit`

### Test Suites
- Unit Tests: `./vendor/bin/phpunit --testsuite Unit`
- Integration Tests: `./vendor/bin/phpunit --testsuite Integration`
- API Tests: `./vendor/bin/phpunit --testsuite API`

## Database Schema

### users
- id (INTEGER PRIMARY KEY)
- username (TEXT UNIQUE)
- created_at (DATETIME)

### groups
- id (INTEGER PRIMARY KEY)
- name (TEXT)
- created_at (DATETIME)

### group_members
- group_id (INTEGER)
- user_id (INTEGER)
- joined_at (DATETIME)
- PRIMARY KEY (group_id, user_id)
- FOREIGN KEY references to groups and users

### messages
- id (INTEGER PRIMARY KEY)
- group_id (INTEGER)
- user_id (INTEGER)
- content (TEXT)
- created_at (DATETIME)
- FOREIGN KEY references to groups and users

## Postman Collection
A Postman collection is available in `docs/postman/Chat_API.postman_collection.json` for testing all endpoints. 

## Running Tests

### Setup Test Environment
1. Make sure Redis server is running
2. No need for SQLite file as tests use in-memory database

### Running All Tests
```bash
./vendor/bin/phpunit
```

### Running Specific Test Suites
```bash
# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run only integration tests
./vendor/bin/phpunit --testsuite Integration

# Run only API tests
./vendor/bin/phpunit --testsuite API
```

### Running Individual Test Files
```bash
# Run specific test file
./vendor/bin/phpunit tests/Unit/Models/UserTest.php

# Run specific test method
./vendor/bin/phpunit --filter testCreateUser tests/Unit/Models/UserTest.php
```

### Test Coverage
- Unit Tests: Model methods and basic functionality
- Integration Tests: Cross-component interactions
- API Tests: HTTP endpoints and responses

### Common Test Cases
- Creating/retrieving users
- Creating/joining groups
- Sending/retrieving messages
- Rate limiting
- Cache invalidation
- Error handling
