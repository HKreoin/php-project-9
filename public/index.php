<?php

use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Hexlet\Code\Repositories\UrlRepository;
use Hexlet\Code\Services\UrlChecker;
use Hexlet\Code\Services\UrlValidator;
use Dotenv\Dotenv;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__);
$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

session_start();

// Создаем контейнер
$container = new \DI\Container();

// Настраиваем контейнер
$container->set('renderer', function () use ($rootPath) {
    $renderer = new PhpRenderer($rootPath . '/templates');
    $renderer->setLayout('layout.phtml');
    return $renderer;
});

$container->set(Messages::class, function () {
    return new Messages();
});

$container->set(UrlRepository::class, function () {
    return new UrlRepository();
});

$container->set(UrlChecker::class, function () {
    return new UrlChecker();
});

$container->set(UrlValidator::class, function () {
    return new UrlValidator();
});

$container->set(\Hexlet\Code\Controllers\UrlController::class, function ($container) {
    return new \Hexlet\Code\Controllers\UrlController(
        $container->get(Messages::class),
        $container->get(UrlRepository::class),
        $container->get(UrlChecker::class),
        $container->get(UrlValidator::class),
        $container->get('renderer')
    );
});

// Создаем приложение с контейнером
$app = AppFactory::createFromContainer($container);

$app->get('/', [\Hexlet\Code\Controllers\UrlController::class, 'index']);
$app->post('/urls', [\Hexlet\Code\Controllers\UrlController::class, 'create']);
$app->get('/urls/{id}', [\Hexlet\Code\Controllers\UrlController::class, 'show']);
$app->get('/urls', [\Hexlet\Code\Controllers\UrlController::class, 'indexUrls']);
$app->post('/urls/{id}/checks', [\Hexlet\Code\Controllers\UrlController::class, 'check']);

$app->run();
