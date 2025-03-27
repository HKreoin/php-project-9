<?php

namespace Hexlet\Code\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use DiDom\Document;

class UrlChecker
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 30,
            'verify' => false,
            'allow_redirects' => true,
            'max_redirects' => 5
        ]);
    }

    public function check(string $url): array
    {
        try {
            $res = $this->client->get($url);
            $body = (string) $res->getBody();
            $document = new Document($body);
            
            return [
                'status_code' => $res->getStatusCode(),
                'h1' => $this->extractMeta($document, 'h1'),
                'title' => $this->extractMeta($document, 'title'),
                'description' => $this->extractMeta($document, 'meta[name="description"]', 'content')
            ];
        } catch (RequestException $e) {
            throw new \Exception('Ошибка при проверке: ' . $e->getMessage());
        }
    }

    private function extractMeta(Document $document, string $selector, ?string $attribute = null): string
    {
        $element = $document->first($selector);
        if (!$element) {
            return '';
        }

        return $attribute ? trim($element->getAttribute($attribute)) : trim($element->text());
    }
} 