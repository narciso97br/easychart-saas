<?php

require_once __DIR__ . '/../../app/core/Database.php';

class AddPlanFieldsToUsers
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                ADD COLUMN plan_id INT UNSIGNED NULL AFTER role,
                ADD COLUMN plan_status ENUM('free','pending','active','past_due','canceled') NOT NULL DEFAULT 'free' AFTER plan_id,
                ADD COLUMN plan_activated_at TIMESTAMP NULL DEFAULT NULL AFTER plan_status,
                ADD COLUMN plan_expires_at TIMESTAMP NULL DEFAULT NULL AFTER plan_activated_at,
                ADD COLUMN asaas_customer_id VARCHAR(100) NULL AFTER plan_expires_at,
                ADD COLUMN asaas_subscription_id VARCHAR(100) NULL AFTER asaas_customer_id,
                ADD CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id);
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                DROP FOREIGN KEY fk_users_plan,
                DROP COLUMN asaas_subscription_id,
                DROP COLUMN asaas_customer_id,
                DROP COLUMN plan_expires_at,
                DROP COLUMN plan_activated_at,
                DROP COLUMN plan_status,
                DROP COLUMN plan_id;
        ";

        $pdo->exec($sql);
    }
}
