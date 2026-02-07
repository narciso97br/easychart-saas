<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

class AdminController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function requireSuperAdmin()
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?c=auth&a=login');
            exit;
        }

        if (($_SESSION['user']['role'] ?? 'user') !== 'super_admin') {
            http_response_code(403);
            echo 'Access denied: admin only.';
            exit;
        }
    }

    public function index()
    {
        $this->requireSuperAdmin();

        $user = $_SESSION['user'];

        // Metrics
        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM users');
        $totalUsers = (int)$stmt->fetch()['c'];

        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM users WHERE last_login_at IS NOT NULL');
        $activeUsers = (int)$stmt->fetch()['c'];

        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM charts');
        $totalCharts = (int)$stmt->fetch()['c'];

        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM spreadsheets');
        $totalSpreadsheets = (int)$stmt->fetch()['c'];

        // User management list
        $sql = 'SELECT u.id, u.full_name, u.email, u.role, u.is_active, u.created_at,
                       COALESCE(c.c_charts, 0) AS charts_count,
                       COALESCE(s.c_sheets, 0) AS sheets_count
                FROM users u
                LEFT JOIN (
                    SELECT user_id, COUNT(*) AS c_charts FROM charts GROUP BY user_id
                ) c ON c.user_id = u.id
                LEFT JOIN (
                    SELECT user_id, COUNT(*) AS c_sheets FROM spreadsheets GROUP BY user_id
                ) s ON s.user_id = u.id
                ORDER BY u.created_at DESC';
        $stmt = $this->pdo->query($sql);
        $users = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/index.php';
    }

    public function toggleRole()
    {
        $this->requireSuperAdmin();

        $currentUserId = $_SESSION['user']['id'];
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0 || $id === $currentUserId) {
            header('Location: ' . BASE_URL . '?c=admin&a=index');
            exit;
        }

        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $newRole = $row['role'] === 'super_admin' ? 'user' : 'super_admin';
            $upd = $this->pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
            $upd->execute(['role' => $newRole, 'id' => $id]);
        }

        header('Location: ' . BASE_URL . '?c=admin&a=index');
        exit;
    }

    public function toggleStatus()
    {
        $this->requireSuperAdmin();

        $currentUserId = $_SESSION['user']['id'];
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0 || $id === $currentUserId) {
            header('Location: ' . BASE_URL . '?c=admin&a=index');
            exit;
        }

        $stmt = $this->pdo->prepare('SELECT is_active FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $newStatus = (int)!((int)$row['is_active']);
            $upd = $this->pdo->prepare('UPDATE users SET is_active = :s WHERE id = :id');
            $upd->execute(['s' => $newStatus, 'id' => $id]);
        }

        header('Location: ' . BASE_URL . '?c=admin&a=index');
        exit;
    }

    public function delete()
    {
        $this->requireSuperAdmin();

        $currentUserId = $_SESSION['user']['id'];
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0 || $id === $currentUserId) {
            header('Location: ' . BASE_URL . '?c=admin&a=index');
            exit;
        }

        $del = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $del->execute(['id' => $id]);

        header('Location: ' . BASE_URL . '?c=admin&a=index');
        exit;
    }

    public function view()
    {
        $this->requireSuperAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . BASE_URL . '?c=admin&a=index');
            exit;
        }

        $sql = 'SELECT u.id, u.full_name, u.email, u.role, u.is_active, u.created_at, u.last_login_at,
                       COALESCE(c.c_charts, 0) AS charts_count,
                       COALESCE(s.c_sheets, 0) AS sheets_count
                FROM users u
                LEFT JOIN (
                    SELECT user_id, COUNT(*) AS c_charts FROM charts GROUP BY user_id
                ) c ON c.user_id = u.id
                LEFT JOIN (
                    SELECT user_id, COUNT(*) AS c_sheets FROM spreadsheets GROUP BY user_id
                ) s ON s.user_id = u.id
                WHERE u.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $userDetail = $stmt->fetch();

        if (!$userDetail) {
            header('Location: ' . BASE_URL . '?c=admin&a=index');
            exit;
        }

        require __DIR__ . '/../views/admin/user.php';
    }

    public function plans()
    {
        $this->requireSuperAdmin();

        $stmt = $this->pdo->prepare('SELECT * FROM plans ORDER BY price_cents ASC');
        $stmt->execute();
        $plans = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/plans_index.php';
    }

    public function editPlan()
    {
        $this->requireSuperAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $plan = null;
        $error = '';
        $success = false;

        if ($id > 0) {
            $stmt = $this->pdo->prepare('SELECT * FROM plans WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $plan = $stmt->fetch() ?: null;
            if (!$plan) {
                $error = 'Plano não encontrado.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $priceCents = (int)($_POST['price_cents'] ?? 0);

            $monthlySpreadsheetLimitRaw = trim((string)($_POST['monthly_spreadsheet_limit'] ?? ''));
            $monthlyChartLimitRaw = trim((string)($_POST['monthly_chart_limit'] ?? ''));
            $monthlyTokenLimitRaw = trim((string)($_POST['monthly_token_limit'] ?? ''));

            $monthlySpreadsheetLimit = $monthlySpreadsheetLimitRaw === '' ? null : (int)$monthlySpreadsheetLimitRaw;
            $monthlyChartLimit = $monthlyChartLimitRaw === '' ? null : (int)$monthlyChartLimitRaw;
            $monthlyTokenLimit = $monthlyTokenLimitRaw === '' ? null : (int)$monthlyTokenLimitRaw;

            $isActive = !empty($_POST['is_active']) ? 1 : 0;

            if ($name === '' || $slug === '') {
                $error = 'Informe nome e slug.';
            } else {
                // Garante slug único (exceto no próprio registro)
                $stmt = $this->pdo->prepare('SELECT id FROM plans WHERE slug = :slug LIMIT 1');
                $stmt->execute(['slug' => $slug]);
                $existing = $stmt->fetch();
                if ($existing && (int)$existing['id'] !== (int)($plan['id'] ?? 0)) {
                    $error = 'Slug já em uso. Escolha outro.';
                }
            }

            if (!$error) {
                if (!empty($plan['id'])) {
                    $stmt = $this->pdo->prepare(
                        'UPDATE plans SET name = :name, slug = :slug, price_cents = :price, '
                        . 'monthly_spreadsheet_limit = :msl, monthly_chart_limit = :mcl, monthly_token_limit = :mtl, '
                        . 'is_active = :active, updated_at = NOW() '
                        . 'WHERE id = :id'
                    );
                    $stmt->execute([
                        'name' => $name,
                        'slug' => $slug,
                        'price' => $priceCents,
                        'msl' => $monthlySpreadsheetLimit,
                        'mcl' => $monthlyChartLimit,
                        'mtl' => $monthlyTokenLimit,
                        'active' => $isActive,
                        'id' => (int)$plan['id'],
                    ]);
                    $success = true;
                } else {
                    $stmt = $this->pdo->prepare(
                        'INSERT INTO plans (name, slug, price_cents, currency, monthly_spreadsheet_limit, monthly_chart_limit, monthly_token_limit, is_active) '
                        . 'VALUES (:name, :slug, :price, :currency, :msl, :mcl, :mtl, :active)'
                    );
                    $stmt->execute([
                        'name' => $name,
                        'slug' => $slug,
                        'price' => $priceCents,
                        'currency' => 'BRL',
                        'msl' => $monthlySpreadsheetLimit,
                        'mcl' => $monthlyChartLimit,
                        'mtl' => $monthlyTokenLimit,
                        'active' => $isActive,
                    ]);
                    $success = true;
                    $id = (int)$this->pdo->lastInsertId();
                }

                $stmt = $this->pdo->prepare('SELECT * FROM plans WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $plan = $stmt->fetch() ?: $plan;
            }
        }

        if (!$plan) {
            $plan = [
                'name' => '',
                'slug' => '',
                'price_cents' => 0,
                'monthly_spreadsheet_limit' => null,
                'monthly_chart_limit' => null,
                'monthly_token_limit' => null,
                'is_active' => 1,
            ];
        }

        require __DIR__ . '/../views/admin/plans_form.php';
    }

    public function deletePlan()
    {
        $this->requireSuperAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $this->pdo->prepare("SELECT slug FROM plans WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            // Evita apagar o plano free/premium por segurança
            $slug = $row['slug'] ?? '';
            if (!in_array($slug, ['free', 'premium'], true)) {
                $del = $this->pdo->prepare('DELETE FROM plans WHERE id = :id');
                $del->execute(['id' => $id]);
            }
        }

        header('Location: ' . BASE_URL . '?c=admin&a=plans');
        exit;
    }
}
