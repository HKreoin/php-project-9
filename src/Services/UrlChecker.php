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
                'h1' => $this->extractH1($document),
                'title' => $this->extractTitle($document),
                'description' => $this->extractDescription($document)
            ];
        } catch (RequestException $e) {
            throw new \Exception('Ошибка при проверке: ' . $e->getMessage());
        }
    }

    private function extractH1(Document $document): string
    {
        $h1 = $document->first('h1');
        return $h1 ? trim($h1->text()) : '';
    }

    private function extractTitle(Document $document): string
    {
        $title = $document->first('title');
        return $title ? trim($title->text()) : '';
    }

    private function extractDescription(Document $document): string
    {
        $description = $document->first('meta[name="description"]');
        return $description ? trim($description->getAttribute('content')) : '';
    }
} 