<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

class PlansController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function requireAuth()
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?c=auth&a=login');
            exit;
        }
    }

    public function index()
    {
        $this->requireAuth();

        $stmt = $this->pdo->prepare('SELECT * FROM plans ORDER BY price_cents ASC');
        $stmt->execute();
        $plans = $stmt->fetchAll();

        $freePlan = null;
        $premiumPlan = null;

        foreach ($plans as $plan) {
            if (($plan['slug'] ?? '') === 'free') {
                $freePlan = $plan;
            } elseif (($plan['slug'] ?? '') === 'premium') {
                $premiumPlan = $plan;
            }
        }

        // Descobre o plano atual do usuário logado
        $currentPlanSlug = null;
        $isAdminUnlimited = false;

        if (!empty($_SESSION['user'])) {
            $role = $_SESSION['user']['role'] ?? 'user';
            if ($role === 'super_admin') {
                // Admin sempre é tratado como plano ilimitado
                $isAdminUnlimited = true;
                $currentPlanSlug = 'admin_unlimited';
            } else {
                $userPlanId = $_SESSION['user']['plan_id'] ?? null;
                if ($userPlanId) {
                    foreach ($plans as $plan) {
                        if ((int)$plan['id'] === (int)$userPlanId) {
                            $currentPlanSlug = $plan['slug'] ?? null;
                            break;
                        }
                    }
                }
            }
        }

        require __DIR__ . '/../views/plans/index.php';
    }
}
