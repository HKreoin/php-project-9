<?php

use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Hexlet\Code\Controllers\UrlController;
use Dotenv\Dotenv;
use Slim\Middleware\ErrorMiddleware;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

// Загружаем переменные окружения из .env файла
$rootPath = dirname(__DIR__);
$dotenv = Dotenv::createImmutable($rootPath);
try {
    $dotenv->load();
    
    if (!isset($_ENV['DATABASE_URL'])) {
        throw new \RuntimeException('DATABASE_URL is not set after loading .env file');
    }
} catch (\Exception $e) {
    die('Error loading .env file: ' . $e->getMessage());
}

session_start();

$container = new \DI\Container();

$container->set('flash', function() {
    return new Messages();
});

$container->set(UrlController::class, function($container) {
    return new UrlController($container->get('flash'));
});

$app = AppFactory::createFromContainer($container);

// Добавляем middleware для статических файлов
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    return $response;
});

$app->get('/', [UrlController::class, 'index']);
$app->post('/urls', [UrlController::class, 'create']);
$app->get('/urls/{id}', [UrlController::class, 'show']);
$app->get('/urls', [UrlController::class, 'indexUrls']);
$app->post('/urls/{id}/checks', [UrlController::class, 'check']);

// Добавляем маршрут для статических файлов
$app->get('/assets/{path:.*}', function ($request, $response, $args) {
    $path = $args['path'];
    $file = __DIR__ . '/../public/assets/' . $path;
    
    if (file_exists($file)) {
        $response->getBody()->write(file_get_contents($file));
        $response = $response->withHeader('Content-Type', mime_content_type($file));
        return $response;
    }
    
    return $response->withStatus(404);
});

$app->run();
