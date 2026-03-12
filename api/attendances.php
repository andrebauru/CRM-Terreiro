<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function whatsappLink(?string $phone): ?string
{
    if (!$phone) {
        return null;
    }
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return null;
    }
    return 'https://wa.me/' . $digits;
}

try {
    $pdo = db();

    if ($action === 'bootstrap') {
        $clientsStmt = $pdo->query('SELECT id, name, email, phone FROM clients ORDER BY name ASC');
        $clients = $clientsStmt->fetchAll();
        foreach ($clients as &$client) {
            $client['whatsapp'] = whatsappLink($client['phone'] ?? null);
        }

        $servicesStmt = $pdo->query('SELECT id, name, price, is_active FROM services WHERE is_active = 1 ORDER BY name ASC');
        $services = $servicesStmt->fetchAll();

        jsonResponse(['ok' => true, 'clients' => $clients, 'services' => $services]);
    }

    if ($action === 'list') {
        $stmt = $pdo->query(
            "SELECT a.id, a.client_id, a.total_amount, a.payment_type, a.is_delinquent, a.is_reversed, a.created_at,
                c.name AS client_name, c.phone AS client_phone,
                    GROUP_CONCAT(s.name SEPARATOR ', ') AS services
             FROM attendances a
             JOIN clients c ON c.id = a.client_id
             LEFT JOIN attendance_services ats ON ats.attendance_id = a.id
             LEFT JOIN services s ON s.id = ats.service_id
             GROUP BY a.id
             ORDER BY a.id DESC
             LIMIT 20"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'detail') {
        $attendanceId = (int)($_GET['attendance_id'] ?? 0);
        if ($attendanceId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Atendimento inválido'], 422);
        }
        $stmt = $pdo->prepare(
            "SELECT a.id, a.client_id, a.notes, a.total_amount, a.payment_type, a.is_delinquent, a.is_reversed,
                    c.name AS client_name
             FROM attendances a
             JOIN clients c ON c.id = a.client_id
             WHERE a.id = ?"
        );
        $stmt->execute([$attendanceId]);
        $attendance = $stmt->fetch();
        if (!$attendance) {
            jsonResponse(['ok' => false, 'message' => 'Atendimento não encontrado'], 404);
        }

        $servicesStmt = $pdo->prepare(
            "SELECT s.id, s.name
             FROM attendance_services ats
             JOIN services s ON s.id = ats.service_id
             WHERE ats.attendance_id = ?"
        );
        $servicesStmt->execute([$attendanceId]);

        jsonResponse([
            'ok' => true,
            'attendance' => $attendance,
            'services' => $servicesStmt->fetchAll(),
        ]);
    }

    if ($action === 'history') {
        $clientId = (int)($_GET['client_id'] ?? 0);
        if ($clientId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Cliente inválido'], 422);
        }
        $stmt = $pdo->prepare(
            "SELECT a.id, a.total_amount, a.payment_type, a.created_at,
                    GROUP_CONCAT(s.name SEPARATOR ', ') AS services
             FROM attendances a
             LEFT JOIN attendance_services ats ON ats.attendance_id = a.id
             LEFT JOIN services s ON s.id = ats.service_id
             WHERE a.client_id = ?
             GROUP BY a.id
             ORDER BY a.id DESC"
        );
        $stmt->execute([$clientId]);
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'installments') {
        $attendanceId = (int)($_GET['attendance_id'] ?? 0);
        if ($attendanceId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Atendimento inválido'], 422);
        }
        $stmt = $pdo->prepare('SELECT id, installment_number, amount, due_date, status, receipt_path FROM attendance_installments WHERE attendance_id = ? ORDER BY installment_number');
        $stmt->execute([$attendanceId]);
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create') {
        $clientId = (int)($_POST['client_id'] ?? 0);
        if ($clientId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Cliente inválido'], 422);
        }
        $serviceIds = $_POST['service_ids'] ?? [];
        if (!is_array($serviceIds) || count($serviceIds) === 0) {
            jsonResponse(['ok' => false, 'message' => 'Selecione pelo menos um serviço'], 422);
        }
        $notes = trim((string)($_POST['notes'] ?? '')) ?: null;
        $paymentType = $_POST['payment_type'] ?? 'cash';
        $isDelinquent = (int)($_POST['is_delinquent'] ?? 0);
        $isReversed = (int)($_POST['is_reversed'] ?? 0);

        $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
        $servicesStmt = $pdo->prepare("SELECT id, name, price FROM services WHERE id IN ($placeholders)");
        $servicesStmt->execute($serviceIds);
        $services = $servicesStmt->fetchAll();
        if (!$services) {
            jsonResponse(['ok' => false, 'message' => 'Serviços inválidos'], 422);
        }

        $total = array_reduce($services, fn($sum, $srv) => $sum + (int)$srv['price'], 0);

        $pdo->beginTransaction();
        $insertAttendance = $pdo->prepare('INSERT INTO attendances (client_id, notes, total_amount, payment_type, is_delinquent, is_reversed) VALUES (?, ?, ?, ?, ?, ?)');
        $insertAttendance->execute([$clientId, $notes, $total, $paymentType, $isDelinquent, $isReversed]);
        $attendanceId = (int)$pdo->lastInsertId();

        $insertService = $pdo->prepare('INSERT INTO attendance_services (attendance_id, service_id, price) VALUES (?, ?, ?)');
        foreach ($services as $service) {
            $insertService->execute([$attendanceId, $service['id'], $service['price']]);
        }

        $installmentsCount = 1;
        $dueDay = (int)($_POST['due_day'] ?? date('d'));

        if ($paymentType === 'installments') {
            $installmentsCount = max(1, (int)($_POST['installments_count'] ?? 1));
            $dueDay = (int)($_POST['due_day'] ?? date('d'));
            if ($dueDay < 1 || $dueDay > 28) {
                $dueDay = 1;
            }
        }

        $perInstallment = $installmentsCount > 1 ? intdiv($total, $installmentsCount) : $total;
        $insertInstallment = $pdo->prepare('INSERT INTO attendance_installments (attendance_id, installment_number, amount, due_date) VALUES (?, ?, ?, ?)');

        $today = new DateTime('today');
        $baseDate = new DateTime('first day of this month');
        $baseDate->setDate((int)$baseDate->format('Y'), (int)$baseDate->format('m'), $dueDay);
        if ($baseDate < $today) {
            $baseDate->modify('+1 month');
        }

        for ($i = 1; $i <= $installmentsCount; $i++) {
            $amount = $perInstallment;
            if ($i === $installmentsCount) {
                $amount = $total - ($perInstallment * ($installmentsCount - 1));
            }

            $dueDate = $paymentType === 'cash' ? $today : (clone $baseDate)->modify('+' . ($i - 1) . ' month');
            $insertInstallment->execute([$attendanceId, $i, $amount, $dueDate->format('Y-m-d')]);
        }

        $pdo->commit();
        jsonResponse(['ok' => true, 'id' => $attendanceId]);
    }

    if ($action === 'update') {
        $attendanceId = (int)($_POST['attendance_id'] ?? 0);
        if ($attendanceId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Atendimento inválido'], 422);
        }
        $notes = trim((string)($_POST['notes'] ?? '')) ?: null;
        $isDelinquent = (int)($_POST['is_delinquent'] ?? 0);
        $isReversed = (int)($_POST['is_reversed'] ?? 0);

        $stmt = $pdo->prepare('UPDATE attendances SET notes = ?, is_delinquent = ?, is_reversed = ? WHERE id = ?');
        $stmt->execute([$notes, $isDelinquent, $isReversed, $attendanceId]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'upload_receipt') {
        $installmentId = (int)($_POST['installment_id'] ?? 0);
        if ($installmentId <= 0 || !isset($_FILES['receipt'])) {
            jsonResponse(['ok' => false, 'message' => 'Dados inválidos'], 422);
        }

        $uploadDir = __DIR__ . '/../uploads/receipts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file = $_FILES['receipt'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['ok' => false, 'message' => 'Falha no upload'], 422);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . $installmentId . '_' . time() . ($ext ? '.' . $ext : '');
        $targetPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            jsonResponse(['ok' => false, 'message' => 'Não foi possível salvar o arquivo'], 500);
        }

        $publicPath = '../uploads/receipts/' . $filename;
        $stmt = $pdo->prepare('UPDATE attendance_installments SET receipt_path = ? WHERE id = ?');
        $stmt->execute([$publicPath, $installmentId]);

        jsonResponse(['ok' => true, 'path' => $publicPath]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
