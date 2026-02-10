<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $pdo = db();

    $counts = [
        'clients' => (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
        'services' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'jobs' => (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn(),
        'jobs_in_progress' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'in_progress'")->fetchColumn(),
    ];

    $stmt = $pdo->query(
        "SELECT j.id, j.title, j.status, j.due_date, c.name AS client_name, s.name AS service_name
         FROM jobs j
         LEFT JOIN clients c ON c.id = j.client_id
         LEFT JOIN services s ON s.id = j.service_id
         ORDER BY j.id DESC
         LIMIT 5"
    );

    jsonResponse([
        'ok' => true,
        'counts' => $counts,
        'latest_jobs' => $stmt->fetchAll(),
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
