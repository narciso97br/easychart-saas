<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$pdo = Database::getConnection();

echo "=== Verificando tabela user_settings ===\n";

try {
    $stmt = $pdo->query('DESCRIBE user_settings');
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Estrutura da tabela user_settings:\n";
    foreach ($result as $row) {
        echo "- {$row['Field']} ({$row['Type']}) - NULL: {$row['Null']} - Default: " . ($row['Default'] ?? 'NULL') . "\n";
    }
    
    echo "\n=== Verificando dados existentes ===\n";
    $stmt = $pdo->query('SELECT * FROM user_settings');
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($settings)) {
        echo "Nenhum registro encontrado em user_settings\n";
    } else {
        foreach ($settings as $setting) {
            echo "User ID: {$setting['user_id']}\n";
            echo "- Email notifications: " . ($setting['notification_email_enabled'] ?? 'NULL') . "\n";
            echo "- Weekly summary: " . ($setting['notification_weekly_summary'] ?? 'NULL') . "\n";
            echo "- Product updates: " . ($setting['notification_product_updates'] ?? 'NULL') . "\n";
            echo "- Created: {$setting['created_at']}\n";
            echo "- Updated: {$setting['updated_at']}\n\n";
        }
    }
    
    echo "=== Testando inserção/atualização ===\n";
    $userId = 1; // Supondo que exista um usuário com ID 1
    
    // Verificar se o usuário existe
    $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "Usuário com ID $userId não encontrado. Criando teste com usuário existente...\n";
        $stmt = $pdo->query('SELECT id, full_name FROM users LIMIT 1');
        $user = $stmt->fetch();
        if ($user) {
            $userId = $user['id'];
            echo "Usando usuário ID: $userId ({$user['full_name']})\n";
        } else {
            echo "Nenhum usuário encontrado na tabela users\n";
            exit;
        }
    }
    
    // Testar UPSERT
    $notifyEmail = 1;
    $notifyWeekly = 0;
    $notifyProduct = 1;
    
    $stmt = $pdo->prepare('SELECT id FROM user_settings WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "Atualizando registro existente...\n";
        $stmt = $pdo->prepare('UPDATE user_settings SET notification_email_enabled = ?, notification_weekly_summary = ?, notification_product_updates = ?, updated_at = NOW() WHERE user_id = ?');
        $result = $stmt->execute([$notifyEmail, $notifyWeekly, $notifyProduct, $userId]);
    } else {
        echo "Inserindo novo registro...\n";
        $stmt = $pdo->prepare('INSERT INTO user_settings (user_id, notification_email_enabled, notification_weekly_summary, notification_product_updates) VALUES (?, ?, ?, ?)');
        $result = $stmt->execute([$userId, $notifyEmail, $notifyWeekly, $notifyProduct]);
    }
    
    echo "Operação " . ($result ? "bem-sucedida" : "falhou") . "\n";
    
    // Verificar resultado
    $stmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    $saved = $stmt->fetch();
    
    if ($saved) {
        echo "Valores salvos:\n";
        echo "- Email notifications: {$saved['notification_email_enabled']}\n";
        echo "- Weekly summary: {$saved['notification_weekly_summary']}\n";
        echo "- Product updates: {$saved['notification_product_updates']}\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
