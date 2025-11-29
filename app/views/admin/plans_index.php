<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>EasyChart - Planos (Admin)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f3f4ff;color:#111827;}
        .layout{min-height:100vh;display:flex;flex-direction:column;}
        .content{flex:1;padding:24px 40px 40px;}
        .page-title{font-size:24px;font-weight:600;margin-bottom:4px;}
        .page-subtitle{font-size:14px;color:#6b7280;margin-bottom:16px;}
        .toolbar{margin-bottom:16px;}
        .btn-primary{display:inline-block;padding:8px 14px;border-radius:999px;border:none;background:#2563eb;color:#ffffff;font-size:13px;font-weight:500;text-decoration:none;}
        .btn-primary:hover{background:#1d4ed8;}
        table{width:100%;border-collapse:collapse;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 12px 24px rgba(15,23,42,0.06);}
        th,td{padding:10px 14px;font-size:13px;text-align:left;border-bottom:1px solid #f3f4f6;}
        th{background:#f9fafb;color:#6b7280;font-weight:500;text-transform:uppercase;font-size:12px;}
        tr:last-child td{border-bottom:none;}
        .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;}
        .badge-active{background:#dcfce7;color:#166534;}
        .badge-inactive{background:#fee2e2;color:#b91c1c;}
        .actions a{margin-right:8px;font-size:13px;text-decoration:none;}
        .actions a.edit{color:#2563eb;}
        .actions a.delete{color:#b91c1c;}
    </style>
</head>
<body>
<div class="layout">
    <main class="content">
        <div class="page-title">Planos</div>
        <div class="page-subtitle">Gerencie os planos disponíveis (limites, preços e status).</div>

        <div class="toolbar">
            <a class="btn-primary" href="<?= BASE_URL ?>?c=admin&a=editPlan">+ Novo plano</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Slug</th>
                <th>Preço</th>
                <th>Uploads/mês</th>
                <th>Gráficos/mês</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($plans)): ?>
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;">Nenhum plano cadastrado.</td></tr>
            <?php else: ?>
                <?php foreach ($plans as $p): ?>
                    <tr>
                        <td><?= (int)$p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['slug']) ?></td>
                        <td>R$ <?= number_format((int)$p['price_cents'] / 100, 2, ',', '.') ?></td>
                        <td><?= $p['monthly_spreadsheet_limit'] === null ? 'Ilimitado' : (int)$p['monthly_spreadsheet_limit'] ?></td>
                        <td><?= $p['monthly_chart_limit'] === null ? 'Ilimitado' : (int)$p['monthly_chart_limit'] ?></td>
                        <td>
                            <?php if (!empty($p['is_active'])): ?>
                                <span class="badge badge-active">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a class="edit" href="<?= BASE_URL ?>?c=admin&a=editPlan&id=<?= (int)$p['id'] ?>">Editar</a>
                            <a class="delete" href="<?= BASE_URL ?>?c=admin&a=deletePlan&id=<?= (int)$p['id'] ?>" onclick="return confirm('Excluir este plano?');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
