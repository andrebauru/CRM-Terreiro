<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { display: flex; align-items: center; justify-content: space-between; }
        .logo { height: 50px; }
        .title { font-size: 18px; font-weight: bold; }
        .section { margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Relatório do Dashboard</div>
            <div>Cliente: <?= htmlspecialchars($settings['client_name'] ?? '') ?></div>
            <div>Empresa: <?= htmlspecialchars($settings['company_name'] ?? '') ?></div>
        </div>
        <?php if (!empty($logoDataUri)): ?>
            <img class="logo" src="<?= $logoDataUri ?>" alt="Logo">
        <?php endif; ?>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Total de Clientes</td><td><?= $stats['totalClients'] ?></td></tr>
                <tr><td>Total de Serviços</td><td><?= $stats['totalServices'] ?></td></tr>
                <tr><td>Total de Tarefas</td><td><?= $stats['totalJobs'] ?></td></tr>
                <tr><td>Tarefas Pendentes</td><td><?= $stats['pendingJobs'] ?></td></tr>
                <tr><td>Tarefas Em Andamento</td><td><?= $stats['inProgressJobs'] ?></td></tr>
                <tr><td>Tarefas Concluídas</td><td><?= $stats['completedJobs'] ?></td></tr>
            </tbody>
        </table>
    </div>
</body>
</html>
