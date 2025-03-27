<?php

namespace Hexlet\Code\Controllers;

use Slim\Flash\Messages;
use Hexlet\Code\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UrlController
{
    private Messages $flash;
    private string $templatesPath;

    public function __construct(Messages $flash)
    {
        $this->flash = $flash;
        $this->templatesPath = dirname(__DIR__, 2) . '/templates';
    }

    private function render(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        include $this->templatesPath . '/' . $template . '.phtml';
        $content = ob_get_clean();

        // Рендерим layout
        $title = $data['title'] ?? 'Анализатор страниц';
        ob_start();
        include $this->templatesPath . '/layout.phtml';
        return ob_get_clean();
    }

    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write($this->render('index', [
            'title' => 'Главная',
            'flash' => $this->flash->getMessages()
        ]));
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $url = $request->getParsedBody()['url'];
        
        if (empty($url)) {
            $this->flash->addMessage('error', 'URL не может быть пустым');
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->flash->addMessage('error', 'Некорректный URL');
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $parsedUrl = parse_url($url);
        $name = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        $db = Database::getConnection();
        
        // Проверяем, существует ли уже такой URL
        $stmt = $db->prepare('SELECT id FROM urls WHERE name = ?');
        $stmt->execute([$name]);
        $existingUrl = $stmt->fetch();

        if ($existingUrl) {
            $this->flash->addMessage('success', 'Страница уже существует');
            return $response->withHeader('Location', "/urls/{$existingUrl['id']}")->withStatus(302);
        }

        $stmt = $db->prepare('INSERT INTO urls (name) VALUES (?) RETURNING id');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();

        $this->flash->addMessage('success', 'Страница успешно добавлена');
        return $response->withHeader('Location', "/urls/{$id}")->withStatus(302);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $db = Database::getConnection();
        
        $stmt = $db->prepare('SELECT * FROM urls WHERE id = ?');
        $stmt->execute([$id]);
        $url = $stmt->fetch();

        if (!$url) {
            $this->flash->addMessage('error', 'Страница не найдена');
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $stmt = $db->prepare('SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC');
        $stmt->execute([$id]);
        $checks = $stmt->fetchAll();

        $response->getBody()->write($this->render('show', [
            'title' => 'Страница',
            'url' => $url,
            'checks' => $checks,
            'flash' => $this->flash->getMessages()
        ]));
        return $response;
    }

    public function indexUrls(Request $request, Response $response): Response
    {
        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT u.*, 
                   uc.status_code as last_status_code, 
                   uc.created_at as last_check_at
            FROM urls u
            LEFT JOIN url_checks uc ON u.id = uc.url_id
            WHERE uc.id = (
                SELECT MAX(id)
                FROM url_checks
                WHERE url_id = u.id
            )
            OR uc.id IS NULL
            ORDER BY u.created_at DESC
        ');
        $urls = $stmt->fetchAll();

        $response->getBody()->write($this->render('urls', [
            'title' => 'Сайты',
            'urls' => $urls,
            'flash' => $this->flash->getMessages()
        ]));
        return $response;
    }

    public function check(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $db = Database::getConnection();
        
        $stmt = $db->prepare('SELECT * FROM urls WHERE id = ?');
        $stmt->execute([$id]);
        $url = $stmt->fetch();

        if (!$url) {
            $this->flash->addMessage('error', 'Страница не найдена');
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        try {
            $client = new Client([
                'timeout' => 30,
                'connect_timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
                'max_redirects' => 5
            ]);
            
            $res = $client->get($url['name']);
            $statusCode = $res->getStatusCode();
            $body = (string) $res->getBody();
            
            // Извлекаем h1
            $h1 = '';
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $body, $matches)) {
                $h1 = trim($matches[1]);
            }

            // Извлекаем title
            $title = '';
            if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $body, $matches)) {
                $title = trim($matches[1]);
            }

            // Извлекаем description
            $description = '';
            if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']/i', $body, $matches)) {
                $description = trim($matches[1]);
            }

            // Извлекаем keywords
            $keywords = '';
            if (preg_match('/<meta[^>]*name=["\']keywords["\'][^>]*content=["\'](.*?)["\']/i', $body, $matches)) {
                $keywords = trim($matches[1]);
            }

            $stmt = $db->prepare('INSERT INTO url_checks (url_id, status_code, h1, title, description, keywords) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$id, $statusCode, $h1, $title, $description, $keywords]);

            $this->flash->addMessage('success', 'Страница успешно проверена');
        } catch (RequestException $e) {
            $this->flash->addMessage('error', 'Произошла ошибка при проверке: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->flash->addMessage('error', 'Произошла ошибка при проверке: ' . $e->getMessage());
        }

        return $response->withHeader('Location', "/urls/{$id}")->withStatus(302);
    }
} 