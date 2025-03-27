<?php

namespace Hexlet\Code\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
            
            return [
                'status_code' => $res->getStatusCode(),
                'h1' => $this->extractH1($body),
                'title' => $this->extractTitle($body),
                'description' => $this->extractDescription($body),
                'keywords' => $this->extractKeywords($body)
            ];
        } catch (RequestException $e) {
            throw new \Exception('Ошибка при проверке: ' . $e->getMessage());
        }
    }

    private function extractH1(string $body): string
    {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $body, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    private function extractTitle(string $body): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $body, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    private function extractDescription(string $body): string
    {
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']/i', $body, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    private function extractKeywords(string $body): string
    {
        if (preg_match('/<meta[^>]*name=["\']keywords["\'][^>]*content=["\'](.*?)["\']/i', $body, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
} 