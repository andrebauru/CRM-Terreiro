<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = (string)($_SESSION['user_role'] ?? 'staff');

try {
    $pdo = db();

    if (!hasTable($pdo, 'chat_groups') || !hasTable($pdo, 'chat_group_members')) {
        jsonResponse(['ok' => true, 'data' => []]);
    }

    if ($action === 'list') {
        $stmt = $pdo->prepare(
            "SELECT
                g.id,
                g.group_key,
                g.name,
                g.created_by,
                g.created_at,
                GROUP_CONCAT(gm_all.user_id ORDER BY gm_all.user_id SEPARATOR ',') AS members_ids
             FROM chat_groups g
             INNER JOIN chat_group_members gm_self
               ON gm_self.group_id = g.id AND gm_self.user_id = ?
             LEFT JOIN chat_group_members gm_all
               ON gm_all.group_id = g.id
             GROUP BY g.id, g.group_key, g.name, g.created_by, g.created_at
             ORDER BY g.created_at DESC, g.id DESC"
        );
        $stmt->execute([$currentUserId]);
        $rows = $stmt->fetchAll() ?: [];

        $data = array_map(static function (array $row): array {
            $members = array_values(array_filter(array_map('intval', explode(',', (string)($row['members_ids'] ?? '')))));
            return [
                'id' => (string)($row['group_key'] ?? ('group_' . (int)$row['id'])),
                'name' => (string)($row['name'] ?? 'Grupo privado'),
                'members_ids' => $members,
                'created_by' => (int)($row['created_by'] ?? 0),
                'conversation_id' => (string)($row['group_key'] ?? ('group_' . (int)$row['id'])),
            ];
        }, $rows);

        jsonResponse(['ok' => true, 'data' => $data]);
    }

    if ($action === 'create') {
        // Apenas admin pode criar grupos de chat
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Apenas administradores podem criar grupos'], 403);
        }

        $name = requireField('name', 'Nome do grupo é obrigatório');
        $membersRaw = (string)($_POST['members_ids'] ?? '[]');

        $decoded = json_decode($membersRaw, true);
        if (!is_array($decoded)) {
            $decoded = array_filter(array_map('trim', explode(',', $membersRaw)), static fn($v) => $v !== '');
        }

        $memberIds = array_values(array_unique(array_filter(array_map('intval', $decoded), static fn(int $id): bool => $id > 0)));
        if (!in_array($currentUserId, $memberIds, true)) {
            $memberIds[] = $currentUserId;
        }

        if (count($memberIds) < 2) {
            jsonResponse(['ok' => false, 'message' => 'Selecione pelo menos um membro além de você'], 422);
        }

        $groupKey = 'group_' . implode('_', $memberIds) . '_' . time();

        $pdo->beginTransaction();

        $insertGroup = $pdo->prepare('INSERT INTO chat_groups (group_key, name, created_by) VALUES (?, ?, ?)');
        $insertGroup->execute([$groupKey, $name, $currentUserId]);
        $groupId = (int)$pdo->lastInsertId();

        $insertMember = $pdo->prepare('INSERT IGNORE INTO chat_group_members (group_id, user_id, added_by) VALUES (?, ?, ?)');
        foreach ($memberIds as $memberId) {
            $insertMember->execute([$groupId, $memberId, $currentUserId]);
        }

        $pdo->commit();

        jsonResponse([
            'ok' => true,
            'data' => [
                'id' => $groupKey,
                'name' => $name,
                'members_ids' => $memberIds,
                'created_by' => $currentUserId,
                'conversation_id' => $groupKey,
            ],
        ]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    safeJsonError($e);
}
