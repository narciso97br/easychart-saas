<?php
// Script para criar a tabela user_settings se não existir

echo "=== CONFIGURAÇÃO DAS PREFERÊNCIAS DE NOTIFICAÇÃO ===\n\n";

try {
    // Tentar conexão com o banco
    require_once 'app/config/config.php';
    
    // Conexão manual para evitar problemas com o Database.php
    $db_config = [
        'host' => '127.0.0.1',
        'dbname' => 'easychart_dev', 
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8'
    ];
    
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Conexão com banco de dados estabelecida\n\n";
    
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Tabela user_settings já existe\n";
        
        // Mostrar estrutura
        $stmt = $pdo->query('DESCRIBE user_settings');
        $columns = $stmt->fetchAll();
        
        echo "\nEstrutura da tabela:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']}) Default: " . ($col['Default'] ?: 'NULL') . "\n";
        }
        
        // Verificar dados
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM user_settings');
        $count = $stmt->fetch()['total'];
        echo "\nRegistros existentes: $count\n";
        
    } else {
        echo "⚠️  Tabela user_settings não existe. Criando...\n";
        
        // Criar tabela
        $sql = "CREATE TABLE `user_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `notification_email_enabled` tinyint(1) NOT NULL DEFAULT 1,
          `notification_weekly_summary` tinyint(1) NOT NULL DEFAULT 1,
          `notification_product_updates` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`),
          CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✅ Tabela user_settings criada com sucesso\n";
    }
    
    // Verificar se há usuários para testar
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
    $userCount = $stmt->fetch()['total'];
    echo "\nUsuários no sistema: $userCount\n";
    
    if ($userCount > 0) {
        // Criar configurações para todos os usuários que não têm
        $stmt = $pdo->query('
            INSERT INTO user_settings (user_id, notification_email_enabled, notification_weekly_summary, notification_product_updates)
            SELECT u.id, 1, 1, 1 
            FROM users u 
            LEFT JOIN user_settings us ON u.id = us.user_id 
            WHERE us.user_id IS NULL
        ');
        
        $inserted = $stmt->rowCount();
        if ($inserted > 0) {
            echo "✅ Configurações padrão criadas para $inserted usuário(s)\n";
        } else {
            echo "✅ Todos os usuários já têm configurações\n";
        }
    }
    
    echo "\n=== TESTE DAS PREFERÊNCIAS ===\n";
    echo "Para testar o funcionamento:\n";
    echo "1. Acesse: http://localhost/Projeto-criacao-graficos/?c=settings&a=index\n";
    echo "2. Altere as checkboxes de notificação\n";
    echo "3. Clique em 'Save Changes'\n";
    echo "4. Verifique se as alterações foram salvas\n\n";
    
    echo "=== STATUS: PRONTO PARA USAR ===\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO DE BANCO DE DADOS: " . $e->getMessage() . "\n\n";
    echo "Soluções possíveis:\n";
    echo "1. Verifique se o MySQL/XAMPP está rodando\n";
    echo "2. Verifique se o banco 'easychart_dev' existe\n";
    echo "3. Verifique se o driver MySQL PDO está habilitado\n";
    echo "4. Execute este script via navegador: http://localhost/Projeto-criacao-graficos/setup_settings.php\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
