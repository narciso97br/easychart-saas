-- Migration SQL para criar o usuário administrador
-- Ajuste o nome do banco se necessário (use o mesmo usado no schema.sql)
-- USE easychart-saas;

-- IMPORTANTE: substitua AQUI_VAI_O_HASH_DA_SENHA_123456 pelo hash gerado em PHP com:
--   <?php echo password_hash('123456', PASSWORD_DEFAULT); ?>
-- antes de executar este script.

INSERT INTO users (
    full_name,
    email,
    cpf,
    password_hash,
    role,
    is_active,
    plan_status,
    created_at
) VALUES (
    'Lucas Vacari',
    'lucas@lrvweb.com.br',
    '46284200846',
    '$2y$10$3YAHki.1HX7vSHh3OaO1JuV1KUdrNfmIkseijCKhn05yCQPP/shIu',
    'super_admin',
    1,
    'free',
    NOW()
);
