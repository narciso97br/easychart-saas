<?php

require_once __DIR__ . '/../core/Database.php';

class PlanHelper
{
    public static function getCurrentPlan(PDO $pdo, int $userId): array
    {
        // Busca usuário com plano associado
        $stmt = $pdo->prepare('SELECT u.plan_id, u.plan_status, p.* FROM users u LEFT JOIN plans p ON u.plan_id = p.id WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        // Se não tiver plano associado, tenta carregar plano free por slug
        if (!$row || !$row['plan_id']) {
            $planStmt = $pdo->prepare("SELECT * FROM plans WHERE slug = 'free' LIMIT 1");
            $planStmt->execute();
            $plan = $planStmt->fetch() ?: null;

            return [
                'plan_status' => 'free',
                'plan'        => $plan,
            ];
        }

        return [
            'plan_status' => $row['plan_status'] ?? 'free',
            'plan'        => $row,
        ];
    }

    public static function canConsumeTokens(PDO $pdo, int $userId, int $tokensToConsume): array
    {
        if (defined('DISABLE_PLAN_LIMITS') && DISABLE_PLAN_LIMITS === true) {
            return [true, null, null, null];
        }

        $info  = self::getCurrentPlan($pdo, $userId);
        $plan  = $info['plan'];
        $status = $info['plan_status'];

        if ($tokensToConsume < 0) {
            $tokensToConsume = 0;
        }

        if ($status === 'active' && isset($plan['slug']) && $plan['slug'] === 'premium') {
            return [true, null, null, null];
        }

        $tokenLimit = $plan['monthly_token_limit'] ?? 0;
        if ($tokenLimit === null) {
            return [true, null, null, null];
        }

        $yearMonth = date('Y-m');

        $stmt = $pdo->prepare('SELECT tokens_used FROM user_token_usage_monthly WHERE user_id = :uid AND year_month = :ym LIMIT 1');
        $stmt->execute([
            'uid' => $userId,
            'ym'  => $yearMonth,
        ]);
        $row = $stmt->fetch();
        $used = $row ? (int)$row['tokens_used'] : 0;

        $remaining = (int)$tokenLimit - $used;

        if ($tokenLimit > 0 && ($tokensToConsume > $remaining)) {
            return [false, 'Limite mensal de tokens atingido para o seu plano. Assine/atualize o plano para continuar.', $used, (int)$tokenLimit];
        }

        return [true, null, $used, (int)$tokenLimit];
    }

    public static function addTokenUsage(PDO $pdo, int $userId, int $tokensUsed): void
    {
        if ($tokensUsed <= 0) {
            return;
        }

        $yearMonth = date('Y-m');

        $stmt = $pdo->prepare(
            'INSERT INTO user_token_usage_monthly (user_id, year_month, tokens_used) '
            . 'VALUES (:uid, :ym, :t) '
            . 'ON DUPLICATE KEY UPDATE tokens_used = tokens_used + VALUES(tokens_used), updated_at = NOW()'
        );
        $stmt->execute([
            'uid' => $userId,
            'ym'  => $yearMonth,
            't'   => $tokensUsed,
        ]);
    }

    public static function canUploadSpreadsheet(PDO $pdo, int $userId): array
    {
        if (defined('DISABLE_PLAN_LIMITS') && DISABLE_PLAN_LIMITS === true) {
            return [true, null];
        }

        $info  = self::getCurrentPlan($pdo, $userId);
        $plan  = $info['plan'];
        $status = $info['plan_status'];

        // Se não houver plano configurado, considera free
        $spreadsheetLimit = $plan['monthly_spreadsheet_limit'] ?? 1;

        // Plano premium ativo: sem limite
        if ($status === 'active' && isset($plan['slug']) && $plan['slug'] === 'premium') {
            return [true, null];
        }

        // Conta uploads deste mês
        $startOfMonth = date('Y-m-01 00:00:00');
        $endOfMonth   = date('Y-m-t 23:59:59');

        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM spreadsheets WHERE user_id = :uid AND created_at BETWEEN :start AND :end');
        $stmt->execute([
            'uid'   => $userId,
            'start' => $startOfMonth,
            'end'   => $endOfMonth,
        ]);
        $count = (int)$stmt->fetch()['c'];

        if ($spreadsheetLimit !== null && $count >= (int)$spreadsheetLimit) {
            return [false, 'No plano Free você pode enviar apenas 1 planilha por mês. Assine o Premium para ter uploads ilimitados.'];
        }

        return [true, null];
    }

    public static function canGenerateCharts(PDO $pdo, int $userId, int $newChartsCount = 1): array
    {
        if (defined('DISABLE_PLAN_LIMITS') && DISABLE_PLAN_LIMITS === true) {
            return [true, null];
        }

        $info  = self::getCurrentPlan($pdo, $userId);
        $plan  = $info['plan'];
        $status = $info['plan_status'];

        $chartLimit = $plan['monthly_chart_limit'] ?? 1;

        // Plano premium ativo: sem limite
        if ($status === 'active' && isset($plan['slug']) && $plan['slug'] === 'premium') {
            return [true, null];
        }

        $startOfMonth = date('Y-m-01 00:00:00');
        $endOfMonth   = date('Y-m-t 23:59:59');

        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM charts WHERE user_id = :uid AND created_at BETWEEN :start AND :end');
        $stmt->execute([
            'uid'   => $userId,
            'start' => $startOfMonth,
            'end'   => $endOfMonth,
        ]);
        $count = (int)$stmt->fetch()['c'];

        if ($chartLimit !== null && ($count >= (int)$chartLimit || ($count + $newChartsCount) > (int)$chartLimit)) {
            return [false, 'No plano Free você pode gerar apenas 1 gráfico por mês. Assine o Premium para desbloquear gráficos ilimitados.'];
        }

        return [true, null];
    }
}
