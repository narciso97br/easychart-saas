<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>EasyChart - Planos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f3f4ff;color:#111827;}
        .layout{min-height:100vh;display:flex;flex-direction:column;}
        .topbar{height:56px;background:#0f172a;color:#e5e7eb;display:flex;align-items:center;justify-content:space-between;padding:0 28px;}
        .topbar-left{display:flex;align-items:center;gap:16px;}
        .logo-mark{display:flex;align-items:center;gap:6px;color:#e5e7eb;font-weight:600;}
        .logo-icon{width:24px;height:24px;border-radius:7px;background:linear-gradient(135deg,#2563eb,#4ade80);display:flex;align-items:center;justify-content:center;}
        .logo-icon-bar{width:4px;border-radius:3px;background:#ffffff;margin:0 1px;}
        .top-nav a{color:#cbd5f5;font-size:14px;margin-right:18px;text-decoration:none;}
        .top-nav a.active{color:#ffffff;font-weight:600;}
        .topbar-right{font-size:14px;display:flex;align-items:center;gap:16px;}
        .topbar-right a{color:#e5e7eb;text-decoration:none;}
        .content{flex:1;padding:24px 40px 40px;}
        .page-title{font-size:24px;font-weight:600;margin-bottom:4px;}
        .page-subtitle{font-size:14px;color:#6b7280;margin-bottom:24px;}
        .plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;max-width:900px;}
        .plan-card{background:#ffffff;border-radius:18px;box-shadow:0 18px 30px rgba(15,23,42,0.08);padding:22px 20px;position:relative;}
        .plan-name{font-size:18px;font-weight:600;margin-bottom:4px;}
        .plan-tag{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#22c55e;margin-bottom:10px;}
        .plan-price{font-size:24px;font-weight:700;margin:10px 0;}
        .plan-price span{font-size:13px;color:#6b7280;font-weight:400;}
        .plan-feature{font-size:13px;color:#374151;margin-bottom:4px;}
        .plan-feature.muted{color:#9ca3af;}
        .btn-primary{display:inline-block;margin-top:14px;padding:9px 18px;border-radius:999px;border:none;background:#2563eb;color:#ffffff;font-size:14px;font-weight:500;text-decoration:none;}
        .btn-primary:hover{background:#1d4ed8;}
        .badge-popular{position:absolute;top:14px;right:16px;background:#22c55e;color:#ecfdf3;font-size:11px;padding:3px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:.05em;}
    </style>
</head>
<body>
<div class="layout">
    <header class="topbar">
        <div class="topbar-left">
            <div class="logo-mark">
                <div class="logo-icon">
                    <div class="logo-icon-bar" style="height:10px;"></div>
                    <div class="logo-icon-bar" style="height:16px;opacity:.85;"></div>
                    <div class="logo-icon-bar" style="height:12px;opacity:.7;"></div>
                </div>
                <span>EasyChart</span>
            </div>
            <nav class="top-nav">
                <a href="<?= BASE_URL ?>?c=dashboard&a=index"><?= Lang::get('Dashboard') ?></a>
                <a href="<?= BASE_URL ?>?c=spreadsheets&a=index"><?= Lang::get('Spreadsheets') ?></a>
                <a href="<?= BASE_URL ?>?c=settings&a=index"><?= Lang::get('Settings') ?></a>
                <a href="<?= BASE_URL ?>?c=plans&a=index" class="active">Planos</a>
            </nav>
        </div>
        <div class="topbar-right">
            <span><?= Lang::get('Welcome') ?>, <?= isset($_SESSION['user']['full_name']) ? htmlspecialchars($_SESSION['user']['full_name']) : 'User' ?></span>
            <a href="<?= BASE_URL ?>?c=dashboard&a=logout"><?= Lang::get('Logout') ?></a>
        </div>
    </header>

    <main class="content">
        <div class="page-title">Escolha o plano ideal para você</div>
        <div class="page-subtitle">Comece grátis e faça o upgrade para ter uploads e gráficos ilimitados quando estiver pronto.</div>

        <?php
        $currentLabel = 'Free';
        $alreadyPremium = false;

        if (!empty($isAdminUnlimited)) {
            $currentLabel = 'Ilimitado (admin)';
            $alreadyPremium = true;
        } elseif (!empty($currentPlanSlug)) {
            if ($currentPlanSlug === 'premium') {
                $currentLabel = 'Premium';
                $alreadyPremium = true;
            } elseif ($currentPlanSlug === 'free') {
                $currentLabel = 'Free';
            }
        }
        ?>

        <div class="page-subtitle" style="margin-top:-8px;margin-bottom:20px;color:#4b5563;">
            Seu plano atual: <strong><?= htmlspecialchars($currentLabel) ?></strong>
        </div>

        <section class="plans-grid">
            <?php if (!empty($freePlan)): ?>
                <article class="plan-card">
                    <div class="plan-name"><?= htmlspecialchars($freePlan['name'] ?? 'Free') ?></div>
                    <div class="plan-price">
                        R$ <?= number_format((int)($freePlan['price_cents'] ?? 0) / 100, 2, ',', '.') ?><span>/mês</span>
                    </div>
                    <div class="plan-feature">
                        <?= ($freePlan['monthly_spreadsheet_limit'] ?? 1) === null
                            ? 'Uploads de planilhas ilimitados'
                            : (int)$freePlan['monthly_spreadsheet_limit'] . ' upload de planilha por mês' ?>
                    </div>
                    <div class="plan-feature">
                        <?= ($freePlan['monthly_chart_limit'] ?? 1) === null
                            ? 'Gráficos gerados ilimitados'
                            : (int)$freePlan['monthly_chart_limit'] . ' gráfico gerado por mês' ?>
                    </div>
                    <div class="plan-feature muted">Suporte padrão por e-mail</div>
                    <div class="plan-feature muted">Ideal para começar a testar o EasyChart</div>
                </article>
            <?php endif; ?>

            <?php if (!empty($premiumPlan)): ?>
                <article class="plan-card">
                    <div class="badge-popular">Mais escolhido</div>
                    <div class="plan-name"><?= htmlspecialchars($premiumPlan['name'] ?? 'Premium') ?></div>
                    <div class="plan-price">
                        R$ <?= number_format((int)($premiumPlan['price_cents'] ?? 0) / 100, 2, ',', '.') ?><span>/mês</span>
                    </div>
                    <div class="plan-feature">
                        <?= ($premiumPlan['monthly_spreadsheet_limit'] ?? null) === null
                            ? 'Uploads de planilhas ilimitados'
                            : (int)$premiumPlan['monthly_spreadsheet_limit'] . ' uploads de planilhas por mês' ?>
                    </div>
                    <div class="plan-feature">
                        <?= ($premiumPlan['monthly_chart_limit'] ?? null) === null
                            ? 'Geração de gráficos ilimitada'
                            : (int)$premiumPlan['monthly_chart_limit'] . ' gráficos por mês' ?>
                    </div>
                    <div class="plan-feature">Suporte prioritário por e-mail</div>
                    <div class="plan-feature">Cobrança automática segura via Asaas</div>

                    <?php if (!empty($alreadyPremium)): ?>
                        <span class="plan-feature" style="font-weight:600;color:#16a34a;">Este é o seu plano atual</span>
                    <?php else: ?>
                        <a class="btn-primary" href="<?= BASE_URL ?>?c=asaas&a=showCheckout">Assinar Premium</a>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
