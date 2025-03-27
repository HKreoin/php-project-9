<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

$container = new Container();

$container->set('renderer', function () {
    $renderer = new PhpRenderer(__DIR__ . '/../templates', ['title' => 'Анализатор страниц']);
    $renderer->setLayout('layout.phtml');
    return $renderer;
});

$app = AppFactory::createFromContainer($container);

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $viewData = [
        'name' => 'John',
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $viewData);
})->setName('hello');

$app->get('/urls', function ($request, $response) {
    $viewData = [
        'name' => 'John',
    ];
    return $this->get('renderer')->render($response, 'urls/index.phtml', $viewData);
})->setName('urls');

$app->run();
