<?php

namespace Hexlet\Code\Controllers;

use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as Response;

class BaseController
{
    protected Messages $flash;
    protected PhpRenderer $renderer;

    public function __construct(Messages $flash, PhpRenderer $renderer)
    {
        $this->flash = $flash;
        $this->renderer = $renderer;
    }

    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->renderer->render($response, $template . '.phtml', $data);
    }

    protected function redirect(Response $response, string $url, int $status = 302): Response
    {
        return $response->withHeader('Location', $url)->withStatus($status);
    }
} 