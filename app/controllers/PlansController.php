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

        require __DIR__ . '/../views/plans/index.php';
    }
}
