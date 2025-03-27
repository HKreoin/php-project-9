<?php

namespace Hexlet\Code\Controllers;

use Slim\Flash\Messages;
use Hexlet\Code\Repositories\UrlRepository;
use Hexlet\Code\Services\UrlChecker;
use Hexlet\Code\Services\UrlValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

class UrlController extends BaseController
{
    private UrlRepository $repository;
    private UrlChecker $checker;
    private UrlValidator $validator;

    public function __construct(
        Messages $flash,
        UrlRepository $repository,
        UrlChecker $checker,
        UrlValidator $validator,
        PhpRenderer $renderer
    ) {
        parent::__construct($flash, $renderer);
        $this->repository = $repository;
        $this->checker = $checker;
        $this->validator = $validator;
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'index', [
            'title' => 'Главная',
            'flash' => $this->flash->getMessages()
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $url = $request->getParsedBody()['url'];
        $errors = $this->validator->validate($url);
        
        if (!empty($errors)) {
            $this->flash->addMessage('error', $errors[0]);
            return $this->redirect($response, '/');
        }

        $name = $this->validator->normalize($url);
        $existingUrl = $this->repository->findByName($name);
        
        if ($existingUrl) {
            $this->flash->addMessage('success', 'Страница уже существует');
            return $this->redirect($response, "/urls/{$existingUrl['id']}");
        }

        $id = $this->repository->create($name);
        $this->flash->addMessage('success', 'Страница успешно добавлена');
        return $this->redirect($response, "/urls/{$id}");
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $url = $this->repository->findById($id);

        if (!$url) {
            $this->flash->addMessage('error', 'Страница не найдена');
            return $this->redirect($response, '/');
        }

        $checks = $this->repository->getChecks($id);
        return $this->render($response, 'show', [
            'title' => 'Страница',
            'url' => $url,
            'checks' => $checks,
            'flash' => $this->flash->getMessages()
        ]);
    }

    public function indexUrls(Request $request, Response $response): Response
    {
        $urls = $this->repository->getAll();
        return $this->render($response, 'urls', [
            'title' => 'Сайты',
            'urls' => $urls,
            'flash' => $this->flash->getMessages()
        ]);
    }

    public function check(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $url = $this->repository->findById($id);

        if (!$url) {
            $this->flash->addMessage('error', 'Страница не найдена');
            return $this->redirect($response, '/');
        }

        try {
            $checkData = $this->checker->check($url['name']);
            $this->repository->createCheck($id, $checkData);
            $this->flash->addMessage('success', 'Страница успешно проверена');
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $this->redirect($response, "/urls/{$id}");
    }
} 