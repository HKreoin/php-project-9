<?php

namespace Hexlet\Code\Services;

class UrlValidator
{
    public function validate(string $url): array
    {
        $errors = [];

        if (empty($url)) {
            $errors[] = 'URL не может быть пустым';
            return $errors;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL';
            return $errors;
        }

        return $errors;
    }

    public function normalize(string $url): string
    {
        $parsedUrl = parse_url($url);
        return $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    }
} 