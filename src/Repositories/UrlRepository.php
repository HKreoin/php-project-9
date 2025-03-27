<?php

namespace Hexlet\Code\Repositories;

use Hexlet\Code\Database;

class UrlRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM urls WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM urls WHERE name = ?');
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO urls (name) VALUES (?) RETURNING id');
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    public function getAll(): array
    {
        return $this->db->query('
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
        ')->fetchAll();
    }

    public function getChecks(int $urlId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC');
        $stmt->execute([$urlId]);
        return $stmt->fetchAll();
    }

    public function createCheck(int $urlId, array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO url_checks (url_id, status_code, h1, title, description, keywords) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $urlId,
            $data['status_code'],
            $data['h1'],
            $data['title'],
            $data['description'],
            $data['keywords']
        ]);
    }
} 