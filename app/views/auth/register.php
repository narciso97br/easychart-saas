<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EasyChart - Create account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            margin:0;
            font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            background:linear-gradient(135deg,#ecfdf5,#ffffff);
            color:#111827;
        }
        .page{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .card{
            background:#ffffff;
            box-shadow:0 20px 40px rgba(15,23,42,0.08);
            border-radius:18px;
            padding:40px 40px 32px;
            width:100%;
            max-width:420px;
        }
        .logo{
            width:48px;
            height:48px;
            border-radius:12px;
            background:linear-gradient(135deg,#16a34a,#4ade80);
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 16px;
        }
        .logo-bar{
            width:8px;
            border-radius:4px;
            background:#ffffff;
            margin:0 2px;
        }
        h1{
            text-align:center;
            margin:0 0 6px;
            font-size:26px;
        }
        .subtitle{
            text-align:center;
            color:#6b7280;
            font-size:14px;
            margin-bottom:24px;
        }
        label{
            display:block;
            font-size:13px;
            font-weight:500;
            color:#374151;
            margin-bottom:6px;
        }
        .input-wrapper{
            position:relative;
            margin-bottom:18px;
        }
        .input-wrapper input{
            width:90%;
            padding:11px 12px 11px 38px;
            border-radius:10px;
            border:1px solid #e5e7eb;
            font-size:14px;
            outline:none;
            transition:border-color .15s, box-shadow .15s;
        }
        .input-wrapper input:focus{
            border-color:#16a34a;
            box-shadow:0 0 0 1px rgba(22,163,74,.18);
        }
        .input-icon{
            position:absolute;
            left:12px;
            top:50%;
            transform:translateY(-50%);
            font-size:14px;
            color:#9ca3af;
        }
        .btn-primary{
            width:100%;
            border:none;
            border-radius:10px;
            padding:11px 0;
            background:#16a34a;
            color:#ffffff;
            font-weight:600;
            font-size:15px;
            cursor:pointer;
            margin-top:8px;
        }
        .btn-primary:hover{
            background:#15803d;
        }
        .muted-link{
            text-align:center;
            margin:16px 0 0;
            font-size:13px;
            color:#6b7280;
            margin-top: 30px;
        }
        .muted-link a{
            color:#16a34a;
            font-weight:500;
            text-decoration:none;
        }
        .muted-link a:hover{
            text-decoration:underline;
        }
        .error{
            margin-bottom:10px;
            font-size:13px;
            color:#b91c1c;
            background:#fee2e2;
            border-radius:8px;
            padding:6px 8px;
        }
        .success{
            margin-bottom:10px;
            font-size:13px;
            color:#166534;
            background:#dcfce7;
            border-radius:8px;
            padding:6px 8px;
        }
        .lang-switcher{
            position:absolute;
            top:20px;
            right:20px;
            display:flex;
            align-items:center;
            gap:4px;
            font-size:13px;
        }
        .lang-switcher a{
            color:#6b7280;
            text-decoration:none;
            padding:2px 6px;
            border-radius:4px;
        }
        .lang-switcher a.active{
            color:#16a34a;
            font-weight:600;
            background:rgba(22,163,74,0.1);
        }
    </style>
</head>
<body>
<div class="page">
    <div class="lang-switcher">
        <a href="<?= BASE_URL ?>?c=lang&a=switch&lang=pt" class="<?= Lang::getCurrentLang() === 'pt' ? 'active' : '' ?>">PT</a>
        <span>|</span>
        <a href="<?= BASE_URL ?>?c=lang&a=switch&lang=en" class="<?= Lang::getCurrentLang() === 'en' ? 'active' : '' ?>">EN</a>
    </div>
    <div class="card">
        <div class="logo">
            <div class="logo-bar" style="height:16px;"></div>
            <div class="logo-bar" style="height:28px;opacity:.85;"></div>
            <div class="logo-bar" style="height:20px;opacity:.7;"></div>
        </div>
        <h1><?= Lang::get('Create Account') ?></h1>
        <p class="subtitle"><?= Lang::get('Sign up to get started') ?></p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success">
                <?= Lang::get('Account created successfully') ?>. Redirecting to login...
                <a href="<?= BASE_URL ?>?c=auth&a=login"><?= Lang::get('Sign in') ?></a>
            </div>
            <script>
                setTimeout(function () {
                    window.location.href = '<?= BASE_URL ?>?c=auth&a=login';
                }, 1500);
            </script>
        <?php endif; ?>

        <form method="post">
            <label><?= Lang::get('Full Name') ?></label>
            <div class="input-wrapper">
                <span class="input-icon">&#128100;</span>
                <input type="text" name="full_name" placeholder="<?= Lang::get('Full Name') ?>" required>
            </div>

            <label><?= Lang::get('CPF') ?></label>
            <div class="input-wrapper">
                <span class="input-icon">ID</span>
                <input type="text" name="cpf" placeholder="CPF" required>
            </div>

            <label><?= Lang::get('Email Address') ?></label>
            <div class="input-wrapper">
                <span class="input-icon">&#9993;</span>
                <input type="email" name="email" placeholder="<?= Lang::get('Email Address') ?>" required>
            </div>

            <label><?= Lang::get('Password') ?></label>
            <div class="input-wrapper">
                <span class="input-icon">&#128274;</span>
                <input type="password" name="password" placeholder="<?= Lang::get('Password') ?>" required>
            </div>

            <label><?= Lang::get('Confirm Password') ?></label>
            <div class="input-wrapper">
                <span class="input-icon">&#128274;</span>
                <input type="password" name="password_confirm" placeholder="<?= Lang::get('Confirm Password') ?>" required>
            </div>

            <button class="btn-primary" type="submit"><?= Lang::get('Create Account') ?></button>
        </form>

        <p class="muted-link">
            <?= Lang::get('Already have an account?') ?>
            <a href="<?= BASE_URL ?>?c=auth&a=login"><?= Lang::get('Sign in') ?></a>
        </p>
    </div>
</div>
</body>
</html>