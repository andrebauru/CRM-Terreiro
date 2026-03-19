<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'bootstrap') {
        $servicesStmt = $pdo->query('SELECT id, name FROM services ORDER BY name ASC');
        jsonResponse(['ok' => true, 'services' => $servicesStmt->fetchAll()]);
    }

    if ($action === 'list' || $action === 'export') {
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $name = trim((string)($_GET['name'] ?? ''));
        $serviceId = (int)($_GET['service_id'] ?? 0);
        $status = $_GET['status'] ?? '';
        $grade = $_GET['grade'] ?? '';
        $source = $_GET['source'] ?? 'trabalhos';

        if ($source === 'mensalidades') {
            // Filtros específicos para query de mensalidades (aliases: mp, f)
            $where = [];
            $params = [];

            if ($start && $end) {
                $where[] = 'DATE(mp.paid_at) BETWEEN ? AND ?';
                $params[] = $start;
                $params[] = $end;
            } elseif ($start) {
                $where[] = 'DATE(mp.paid_at) >= ?';
                $params[] = $start;
            } elseif ($end) {
                $where[] = 'DATE(mp.paid_at) <= ?';
                $params[] = $end;
            }

            if ($name !== '') {
                $where[] = 'f.name LIKE ?';
                $params[] = '%' . $name . '%';
            }

            if ($grade !== '') {
                $where[] = 'f.grade = ?';
                $params[] = $grade;
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $pdo->prepare(
                "SELECT mp.id, DATE(mp.paid_at) AS date, mp.amount AS total_amount,
                        'Mensalidade' AS payment_type, f.name AS client_name,
                        f.grade AS services
                 FROM mensalidades_pagas mp
                 JOIN filhos f ON f.id = mp.filho_id
                 $whereSql
                 ORDER BY mp.id DESC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $sumStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(mp.amount),0)
                 FROM mensalidades_pagas mp
                 JOIN filhos f ON f.id = mp.filho_id
                 $whereSql"
            );
            $sumStmt->execute($params);
            $total = (float)$sumStmt->fetchColumn();
        } else {
            // Filtros específicos para query de trabalhos (aliases: a, c, s)
            $where = [];
            $params = [];

            if ($start && $end) {
                $where[] = 'DATE(a.created_at) BETWEEN ? AND ?';
                $params[] = $start;
                $params[] = $end;
            } elseif ($start) {
                $where[] = 'DATE(a.created_at) >= ?';
                $params[] = $start;
            } elseif ($end) {
                $where[] = 'DATE(a.created_at) <= ?';
                $params[] = $end;
            }

            if ($name !== '') {
                $where[] = 'c.name LIKE ?';
                $params[] = '%' . $name . '%';
            }

            if ($serviceId > 0) {
                $where[] = 's.id = ?';
                $params[] = $serviceId;
            }

            if ($status === 'delinquent') {
                $where[] = 'a.is_delinquent = 1';
            }
            if ($status === 'reversed') {
                $where[] = 'a.is_reversed = 1';
            }
            if ($status === 'paid') {
                $where[] = 'a.is_delinquent = 0';
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $pdo->prepare(
                "SELECT a.id, DATE(a.created_at) AS date, a.total_amount, a.payment_type, c.name AS client_name,
                        GROUP_CONCAT(s.name SEPARATOR ', ') AS services
                 FROM attendances a
                 JOIN clients c ON c.id = a.client_id
                 LEFT JOIN attendance_services ats ON ats.attendance_id = a.id
                 LEFT JOIN services s ON s.id = ats.service_id
                 $whereSql
                 GROUP BY a.id
                 ORDER BY a.id DESC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $sumStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(t.total_amount),0) FROM (
                    SELECT a.id, a.total_amount
                    FROM attendances a
                    JOIN clients c ON c.id = a.client_id
                    LEFT JOIN attendance_services ats ON ats.attendance_id = a.id
                    LEFT JOIN services s ON s.id = ats.service_id
                    $whereSql
                    GROUP BY a.id
                ) t"
            );
            $sumStmt->execute($params);
            $total = (float)$sumStmt->fetchColumn();
        }

        if ($action === 'export') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="relatorio_atendimentos.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Data', 'Cliente', 'Serviços', 'Pagamento', 'Total (JPY)']);
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['client_name'],
                    $row['services'] ?? '-',
                    $row['payment_type'] === 'cash' ? 'À Vista' : 'Parcelado',
                    (int)$row['total_amount'],
                ]);
            }
            fclose($output);
            exit;
        }

        jsonResponse(['ok' => true, 'data' => $rows, 'total' => $total]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
