<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = $_SESSION['user_role'] ?? 'staff';

try {
    $pdo = db();

    // Garante que a tabela existe
    if (!hasTable($pdo, 'profile_pending_changes')) {
        jsonResponse(['ok' => false, 'message' => 'Tabela de alterações pendentes não existe'], 500);
    }

    // ========== ACTION: list ==========
    if ($action === 'list') {
        // Apenas admin pode listar alterações pendentes
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $status = $_GET['status'] ?? 'pending'; // pending, approved, rejected
        $stmt = $pdo->prepare(
            "SELECT
                ppc.id,
                ppc.user_id,
                ppc.name,
                ppc.email,
                ppc.foto_perfil,
                ppc.other_data,
                ppc.status,
                ppc.requested_at,
                ppc.reviewed_at,
                ppc.reviewed_by,
                ppc.review_notes,
                u.name AS user_name,
                u.email AS user_email,
                reviewer.name AS reviewer_name
             FROM profile_pending_changes ppc
             LEFT JOIN users u ON u.id = ppc.user_id
             LEFT JOIN users reviewer ON reviewer.id = ppc.reviewed_by
             WHERE ppc.status = ?
             ORDER BY ppc.requested_at DESC"
        );
        $stmt->execute([$status]);
        $rows = $stmt->fetchAll() ?: [];

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'user_id' => (int)$row['user_id'],
                'user_name' => (string)($row['user_name'] ?? ''),
                'user_email' => (string)($row['user_email'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'foto_perfil' => (string)($row['foto_perfil'] ?? ''),
                'other_data' => json_decode((string)($row['other_data'] ?? '{}'), true),
                'status' => (string)$row['status'],
                'requested_at' => (string)$row['requested_at'],
                'reviewed_at' => (string)($row['reviewed_at'] ?? ''),
                'reviewed_by' => $row['reviewed_by'] ? (int)$row['reviewed_by'] : null,
                'reviewer_name' => (string)($row['reviewer_name'] ?? ''),
                'review_notes' => (string)($row['review_notes'] ?? ''),
            ];
        }, $rows);

        jsonResponse(['ok' => true, 'data' => $data]);
    }

    // ========== ACTION: request (usuário comum solicita alteração) ==========
    if ($action === 'request') {
        $userId = (int)requireField('user_id', 'user_id é obrigatório');
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $fotoPerfil = trim((string)($_POST['foto_perfil'] ?? ''));
        $newPassword = trim((string)($_POST['password'] ?? ''));

        // Verificar que o usuário está editando seu próprio perfil
        if ($userId !== $currentUserId && $currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Você pode editar apenas seu próprio perfil'], 403);
        }

        $pdo->beginTransaction();

        try {
            // Se for admin, aplica direto na tabela de usuários
            if ($currentUserRole === 'admin') {
                $updates = [];
                $params = [];

                if ($name !== '') {
                    $updates[] = 'name = ?';
                    $params[] = $name;
                }
                if ($email !== '') {
                    $updates[] = 'email = ?';
                    $params[] = $email;
                }
                if ($fotoPerfil !== '') {
                    $updates[] = 'foto_perfil = ?';
                    $params[] = $fotoPerfil;
                }
                if ($newPassword !== '') {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $updates[] = 'password = ?';
                    $params[] = $hashedPassword;
                }

                if (!empty($updates)) {
                    $params[] = $userId;
                    $updateSQL = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
                    $stmt = $pdo->prepare($updateSQL);
                    $stmt->execute($params);
                }

                $pdo->commit();
                jsonResponse(['ok' => true, 'message' => 'Perfil atualizado com sucesso', 'direct_update' => true]);
            } else {
                // Se for usuário comum, salva na tabela de pendências
                $otherData = [];

                // Verifica se email já existe em outro usuário
                if ($email !== '') {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                    $stmt->execute([$email, $userId]);
                    if ($stmt->fetch()) {
                        throw new \Exception('Email já cadastrado em outro usuário');
                    }
                }

                $insertStmt = $pdo->prepare(
                    "INSERT INTO profile_pending_changes 
                     (user_id, name, email, foto_perfil, password_hash, other_data, status)
                     VALUES (?, ?, ?, ?, ?, ?, 'pending')"
                );

                $passwordHash = $newPassword !== '' ? password_hash($newPassword, PASSWORD_BCRYPT) : null;
                $otherDataJson = json_encode($otherData);

                $insertStmt->execute([
                    $userId,
                    $name !== '' ? $name : null,
                    $email !== '' ? $email : null,
                    $fotoPerfil !== '' ? $fotoPerfil : null,
                    $passwordHash,
                    $otherDataJson,
                ]);

                $pdo->commit();
                jsonResponse([
                    'ok' => true,
                    'message' => 'Alterações enviadas para aprovação',
                    'pending_approval' => true,
                ]);
            }
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ========== ACTION: approve (admin aprova) ==========
    if ($action === 'approve') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $changeId = (int)requireField('change_id', 'change_id é obrigatório');
        $reviewNotes = trim((string)($_POST['review_notes'] ?? ''));

        $pdo->beginTransaction();

        try {
            // Busca a alteração pendente
            $stmt = $pdo->prepare('SELECT * FROM profile_pending_changes WHERE id = ? AND status = ?');
            $stmt->execute([$changeId, 'pending']);
            $change = $stmt->fetch();

            if (!$change) {
                throw new \Exception('Alteração não encontrada ou já processada');
            }

            $userId = (int)$change['user_id'];
            $updates = [];
            $params = [];

            if ($change['name']) {
                $updates[] = 'name = ?';
                $params[] = $change['name'];
            }
            if ($change['email']) {
                $updates[] = 'email = ?';
                $params[] = $change['email'];
            }
            if ($change['foto_perfil']) {
                $updates[] = 'foto_perfil = ?';
                $params[] = $change['foto_perfil'];
            }
            if ($change['password_hash']) {
                $updates[] = 'password = ?';
                $params[] = $change['password_hash'];
            }

            // Aplica as alterações na tabela de usuários
            if (!empty($updates)) {
                $params[] = $userId;
                $updateSQL = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
                $updateStmt = $pdo->prepare($updateSQL);
                $updateStmt->execute($params);
            }

            // Marca como aprovada
            $approveStmt = $pdo->prepare(
                "UPDATE profile_pending_changes 
                 SET status = 'approved', reviewed_at = NOW(), reviewed_by = ?, review_notes = ?
                 WHERE id = ?"
            );
            $approveStmt->execute([$currentUserId, $reviewNotes, $changeId]);

            $pdo->commit();
            jsonResponse(['ok' => true, 'message' => 'Alteração aprovada com sucesso']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ========== ACTION: reject (admin rejeita) ==========
    if ($action === 'reject') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $changeId = (int)requireField('change_id', 'change_id é obrigatório');
        $reviewNotes = trim((string)($_POST['review_notes'] ?? 'Rejeitado pelo administrador'));

        $pdo->beginTransaction();

        try {
            // Marca como rejeitada
            $rejectStmt = $pdo->prepare(
                "UPDATE profile_pending_changes 
                 SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ?, review_notes = ?
                 WHERE id = ? AND status = 'pending'"
            );
            $rejectStmt->execute([$currentUserId, $reviewNotes, $changeId]);

            if ($rejectStmt->rowCount() === 0) {
                throw new \Exception('Alteração não encontrada ou já processada');
            }

            $pdo->commit();
            jsonResponse(['ok' => true, 'message' => 'Alteração rejeitada']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ========== ACTION: get-pending-count (para exibir badge no frontend) ==========
    if ($action === 'get-pending-count') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM profile_pending_changes WHERE status = ?');
        $stmt->execute(['pending']);
        $row = $stmt->fetch();

        jsonResponse(['ok' => true, 'count' => (int)($row['count'] ?? 0)]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['ok' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
}
