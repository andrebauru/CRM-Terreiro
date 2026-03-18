<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function monthStart(DateTime $date): string
{
    return $date->format('Y-m-01');
}

try {
    $pdo = db();

    // List realizações
    if ($action === 'list') {
        $stmt = $pdo->query(
            "SELECT r.id, r.trabalho_id, t.name AS trabalho_nome, t.price,
                    r.cliente_nome, r.data_realizacao, r.status, r.nova_data, r.observacoes
             FROM trabalho_realizacoes r
             JOIN trabalhos t ON t.id = r.trabalho_id
             ORDER BY r.data_realizacao DESC, r.id DESC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // List catálogo de trabalhos
    if ($action === 'list_catalogo') {
        $stmt = $pdo->query('SELECT id, name, description, price, is_active FROM trabalhos WHERE is_active = 1 ORDER BY name ASC');
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
        $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Pendente';
        $novaData = trim((string)($_POST['nova_data'] ?? '')) ?: null;
        $obs = trim((string)($_POST['observacoes'] ?? '')) ?: null;

        $stmt = $pdo->prepare('INSERT INTO trabalho_realizacoes (trabalho_id, cliente_nome, data_realizacao, status, nova_data, observacoes) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$trabalhoId, $clienteNome, $dataRealizacao, $status, $novaData, $obs]);
        $newId = (int)$pdo->lastInsertId();

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
        $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Pendente';
        $novaData = trim((string)($_POST['nova_data'] ?? '')) ?: null;
        $obs = trim((string)($_POST['observacoes'] ?? '')) ?: null;

        $stmt = $pdo->prepare('UPDATE trabalho_realizacoes SET trabalho_id = ?, cliente_nome = ?, data_realizacao = ?, status = ?, nova_data = ?, observacoes = ? WHERE id = ?');
        $stmt->execute([$trabalhoId, $clienteNome, $dataRealizacao, $status, $novaData, $obs, $id]);

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
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
