<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EasyChart - User Detail</title>
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
        .card{background:#ffffff;border-radius:16px;box-shadow:0 18px 30px rgba(15,23,42,0.08);padding:18px 20px;max-width:720px;}
        .card-title{font-size:20px;font-weight:600;margin-bottom:4px;}
        .card-subtitle{font-size:14px;color:#6b7280;margin-bottom:16px;}
        .row{margin-bottom:8px;font-size:14px;}
        .label{font-weight:500;color:#374151;margin-right:6px;}
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
                <a href="<?= BASE_URL ?>?c=admin&a=index" class="active">Admin Panel</a>
            </nav>
        </div>
        <div class="topbar-right">
            <span>Welcome, <?= isset($_SESSION['user']['full_name']) ? htmlspecialchars($_SESSION['user']['full_name']) : 'User' ?></span>
            <a href="<?= BASE_URL ?>?c=dashboard&a=logout">Logout</a>
        </div>
    </header>

    <main class="content">
        <div class="card">
            <div class="card-title">User Details</div>
            <div class="card-subtitle">Overview of the selected user.</div>

            <div class="row"><span class="label">Name:</span> <?= htmlspecialchars($userDetail['full_name']) ?></div>
            <div class="row"><span class="label">Email:</span> <?= htmlspecialchars($userDetail['email']) ?></div>
            <div class="row"><span class="label">Role:</span> <?= htmlspecialchars($userDetail['role']) ?></div>
            <div class="row"><span class="label">Status:</span> <?= !empty($userDetail['is_active']) ? 'Active' : 'Inactive' ?></div>
            <div class="row"><span class="label">Joined:</span> <?= htmlspecialchars($userDetail['created_at']) ?></div>
            <div class="row"><span class="label">Last Login:</span> <?= htmlspecialchars($userDetail['last_login_at'] ?? 'never') ?></div>
            <div class="row"><span class="label">Charts:</span> <?= (int)$userDetail['charts_count'] ?></div>
            <div class="row"><span class="label">Spreadsheets:</span> <?= (int)$userDetail['sheets_count'] ?></div>

            <div style="margin-top:16px;">
                <a href="<?= BASE_URL ?>?c=admin&a=index">Back to Admin Panel</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
