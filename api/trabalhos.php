<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function monthStart(DateTime $date): string
{
    return $date->format('Y-m-01');
}

try {
    $pdo = db();

    // Auto-migrate: ensure trabalhos.price is INT (older schemas used DECIMAL)
    try {
        $colInfo = $pdo->query("SHOW COLUMNS FROM trabalhos WHERE Field = 'price'")->fetch();
        if ($colInfo && stripos($colInfo['Type'], 'decimal') !== false) {
            $pdo->exec('ALTER TABLE trabalhos MODIFY COLUMN price INT NOT NULL DEFAULT 0');
        }
    } catch (Throwable $e) { /* table may not exist yet */ }

    // List realizações
    if ($action === 'list') {
        $stmt = $pdo->query(
            "SELECT r.id, r.trabalho_id, r.client_id, r.attendance_id, t.name AS trabalho_nome, t.price,
                    r.cliente_nome, r.data_realizacao, r.status, r.nova_data, r.data_pagamento, r.observacoes
             FROM trabalho_realizacoes r
             JOIN trabalhos t ON t.id = r.trabalho_id
             ORDER BY r.data_realizacao DESC, r.id DESC"
        );
        $rows = $stmt->fetchAll();
        // Carregar datas extras para cada realização
        foreach ($rows as &$row) {
            $datasStmt = $pdo->prepare('SELECT data_extra FROM trabalho_datas_extras WHERE trabalho_realizacao_id = ? ORDER BY data_extra ASC');
            $datasStmt->execute([$row['id']]);
            $row['datas_extras'] = array_column($datasStmt->fetchAll(), 'data_extra');
        }
        jsonResponse(['ok' => true, 'data' => $rows]);
    }

    // List catálogo de trabalhos
    if ($action === 'list_catalogo') {
        $stmt = $pdo->query(
            "SELECT id, name, description, price, is_active, 'trabalho' AS tipo FROM trabalhos WHERE is_active = 1
             UNION ALL
             SELECT id, name, description, price, is_active, 'servico' AS tipo FROM services WHERE is_active = 1
             ORDER BY name ASC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // List catálogo (all)
    if ($action === 'list_catalogo_all') {
        $stmt = $pdo->query('SELECT id, name, description, price, is_active FROM trabalhos ORDER BY id DESC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // Create catálogo entry
    if ($action === 'create_catalogo') {
        $name = requireField('name', 'Nome obrigatório');
        $description = trim((string)($_POST['description'] ?? '')) ?: null;
        $price = (int)($_POST['price'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);

        $stmt = $pdo->prepare('INSERT INTO trabalhos (name, description, price, is_active) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $description, $price, $isActive]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    // Update catálogo entry
    if ($action === 'update_catalogo') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $name = requireField('name', 'Nome obrigatório');
        $description = trim((string)($_POST['description'] ?? '')) ?: null;
        $price = (int)($_POST['price'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);

        $stmt = $pdo->prepare('UPDATE trabalhos SET name = ?, description = ?, price = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$name, $description, $price, $isActive, $id]);
        jsonResponse(['ok' => true]);
    }

    // Delete catálogo entry
    if ($action === 'delete_catalogo') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        // Check if any trabalho_realizacoes reference this entry
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM trabalho_realizacoes WHERE trabalho_id = ?');
        $checkStmt->execute([$id]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            // Soft delete: just deactivate
            $stmt = $pdo->prepare('UPDATE trabalhos SET is_active = 0 WHERE id = ?');
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM trabalhos WHERE id = ?');
            $stmt->execute([$id]);
        }
        jsonResponse(['ok' => true]);
    }

    // Create realização
    if ($action === 'create') {
        $trabalhoId = (int)($_POST['trabalho_id'] ?? 0);
        if ($trabalhoId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Trabalho inválido'], 422);
        }
        $priceStmt = $pdo->prepare('SELECT price, name FROM trabalhos WHERE id = ?');
        $priceStmt->execute([$trabalhoId]);
        $trabalhoInfo = $priceStmt->fetch();
        if (!$trabalhoInfo) {
            jsonResponse(['ok' => false, 'message' => 'Trabalho não encontrado'], 404);
        }
        $clienteNome = trim((string)($_POST['cliente_nome'] ?? '')) ?: null;
        $clientId = ((int)($_POST['client_id'] ?? 0)) ?: null;
        $attendanceId = ((int)($_POST['attendance_id'] ?? 0)) ?: null;
        $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Pendente';
        $novaData = trim((string)($_POST['nova_data'] ?? '')) ?: null;
        $dataPagamento = $_POST['data_pagamento'] ?? null;
        $obs = trim((string)($_POST['observacoes'] ?? '')) ?: null;
        $datasExtras = isset($_POST['datas_extras']) ? json_decode($_POST['datas_extras'], true) : [];

        $stmt = $pdo->prepare('INSERT INTO trabalho_realizacoes (trabalho_id, attendance_id, cliente_nome, client_id, data_realizacao, status, nova_data, data_pagamento, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$trabalhoId, $attendanceId, $clienteNome, $clientId, $dataRealizacao, $status, $novaData, $dataPagamento, $obs]);
        $newId = (int)$pdo->lastInsertId();

        // Salvar datas extras
        if (is_array($datasExtras)) {
            foreach ($datasExtras as $dataExtra) {
                if ($dataExtra) {
                    $stmtExtra = $pdo->prepare('INSERT INTO trabalho_datas_extras (trabalho_realizacao_id, data_extra) VALUES (?, ?)');
                    $stmtExtra->execute([$newId, $dataExtra]);
                }
            }
        }

        if ($status === 'Realizado') {
            $dateObj = new DateTime($dataRealizacao);
            $month = monthStart($dateObj);
            $insertMov = $pdo->prepare(
                "INSERT INTO caixa_movimentos (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
                 VALUES ('entrada', 'trabalho', ?, ?, ?, ?, 'realizado', ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), data_movimento = VALUES(data_movimento), status = 'realizado'"
            );
            $insertMov->execute([
                $newId,
                $month,
                $dateObj->format('Y-m-d'),
                (int)$trabalhoInfo['price'],
                'Trabalho - ' . $trabalhoInfo['name'],
            ]);
        }

        jsonResponse(['ok' => true, 'id' => $newId]);
    }

    // Update realização
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $trabalhoId = (int)($_POST['trabalho_id'] ?? 0);
        $priceStmt = $pdo->prepare('SELECT price, name FROM trabalhos WHERE id = ?');
        $priceStmt->execute([$trabalhoId]);
        $trabalhoInfo = $priceStmt->fetch();
        if (!$trabalhoInfo) {
            jsonResponse(['ok' => false, 'message' => 'Trabalho não encontrado'], 404);
        }
        $clienteNome = trim((string)($_POST['cliente_nome'] ?? '')) ?: null;
        $clientId = ((int)($_POST['client_id'] ?? 0)) ?: null;
        $attendanceId = ((int)($_POST['attendance_id'] ?? 0)) ?: null;
        $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Pendente';
        $novaData = trim((string)($_POST['nova_data'] ?? '')) ?: null;
        $dataPagamento = $_POST['data_pagamento'] ?? null;
        $obs = trim((string)($_POST['observacoes'] ?? '')) ?: null;
        $datasExtras = isset($_POST['datas_extras']) ? json_decode($_POST['datas_extras'], true) : [];

        $stmt = $pdo->prepare('UPDATE trabalho_realizacoes SET trabalho_id = ?, attendance_id = ?, cliente_nome = ?, client_id = ?, data_realizacao = ?, status = ?, nova_data = ?, data_pagamento = ?, observacoes = ? WHERE id = ?');
        $stmt->execute([$trabalhoId, $attendanceId, $clienteNome, $clientId, $dataRealizacao, $status, $novaData, $dataPagamento, $obs, $id]);

        // Atualizar datas extras
        $pdo->prepare('DELETE FROM trabalho_datas_extras WHERE trabalho_realizacao_id = ?')->execute([$id]);
        if (is_array($datasExtras)) {
            foreach ($datasExtras as $dataExtra) {
                if ($dataExtra) {
                    $stmtExtra = $pdo->prepare('INSERT INTO trabalho_datas_extras (trabalho_realizacao_id, data_extra) VALUES (?, ?)');
                    $stmtExtra->execute([$id, $dataExtra]);
                }
            }
        }

        $pdo->prepare("DELETE FROM caixa_movimentos WHERE origem = 'trabalho' AND referencia_id = ?")
            ->execute([$id]);

        if ($status === 'Realizado') {
            $dateObj = new DateTime($dataRealizacao);
            $month = monthStart($dateObj);
            $insertMov = $pdo->prepare(
                "INSERT INTO caixa_movimentos (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
                 VALUES ('entrada', 'trabalho', ?, ?, ?, ?, 'realizado', ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), data_movimento = VALUES(data_movimento), status = 'realizado'"
            );
            $insertMov->execute([
                $id,
                $month,
                $dateObj->format('Y-m-d'),
                (int)$trabalhoInfo['price'],
                'Trabalho - ' . $trabalhoInfo['name'],
            ]);
        }
        jsonResponse(['ok' => true]);
    }
    // Listar datas extras de uma realização específica
    if ($action === 'list_datas_extras') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $stmt = $pdo->prepare('SELECT data_extra FROM trabalho_datas_extras WHERE trabalho_realizacao_id = ? ORDER BY data_extra ASC');
        $stmt->execute([$id]);
        $datas = array_column($stmt->fetchAll(), 'data_extra');
        jsonResponse(['ok' => true, 'data' => $datas]);
    }

    // Delete realização
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $pdo->prepare("DELETE FROM caixa_movimentos WHERE origem = 'trabalho' AND referencia_id = ?")
            ->execute([$id]);
        $stmt = $pdo->prepare('DELETE FROM trabalho_realizacoes WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
