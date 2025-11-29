<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/AsaasClient.php';

class AsaasController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function requireAuth()
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?c=auth&a=login');
            exit;
        }
    }

    public function showCheckout()
    {
        $this->requireAuth();
        $userSession = $_SESSION['user'];
        $userId = (int)$userSession['id'];

        $stmt = $this->pdo->prepare('SELECT full_name, email, cpf, phone FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $userRow = $stmt->fetch();

        // Carrega info do plano premium (opcional, para mostrar preço/benefícios)
        $planStmt = $this->pdo->prepare("SELECT * FROM plans WHERE slug = 'premium' LIMIT 1");
        $planStmt->execute();
        $premiumPlan = $planStmt->fetch();

        require __DIR__ . '/../views/asaas/checkout.php';
    }

    public function subscribePremium()
    {
        $this->requireAuth();
        $userSession = $_SESSION['user'];
        $userId = (int)$userSession['id'];

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?c=settings&a=index');
            exit;
        }

        // Dados do formulário
        $fullName = trim($_POST['full_name'] ?? '');
        $cpf      = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
        $phone    = preg_replace('/\D+/', '', $_POST['phone'] ?? '');

        $cardHolder = trim($_POST['card_holder_name'] ?? '');
        $cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
        $expMonth   = trim($_POST['card_exp_month'] ?? '');
        $expYear    = trim($_POST['card_exp_year'] ?? '');
        $cardCvv    = trim($_POST['card_cvv'] ?? '');

        if ($fullName === '' || $cpf === '' || $phone === '' || $cardHolder === '' || $cardNumber === '' || $expMonth === '' || $expYear === '' || $cardCvv === '') {
            $error = 'Preencha todos os campos obrigatórios para assinar o plano Premium.';
        }

        // Carrega dados atuais do usuário
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $userRow = $stmt->fetch();

        if (!$userRow) {
            $error = 'Usuário não encontrado.';
        }

        if (!$error) {
            try {
                // Em ambiente de desenvolvimento, não chamar o Asaas e apenas promover o usuário para Premium
                if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                    $planStmt = $this->pdo->prepare("SELECT id FROM plans WHERE slug = 'premium' LIMIT 1");
                    $planStmt->execute();
                    $plan = $planStmt->fetch();
                    $premiumPlanId = $plan ? (int)$plan['id'] : null;

                    if (!$premiumPlanId) {
                        throw new RuntimeException('Plano Premium não encontrado na tabela plans.');
                    }

                    $upd = $this->pdo->prepare('UPDATE users SET full_name = :name, cpf = :cpf, phone = :phone, plan_id = :plan_id, plan_status = :status, plan_activated_at = NOW(), plan_expires_at = NULL WHERE id = :id');
                    $upd->execute([
                        'name'    => $fullName,
                        'cpf'     => $cpf,
                        'phone'   => $phone,
                        'plan_id' => $premiumPlanId,
                        'status'  => 'active',
                        'id'      => $userId,
                    ]);

                    $_SESSION['user']['full_name'] = $fullName;
                    $_SESSION['user']['plan_id'] = $premiumPlanId;
                    $_SESSION['user']['plan_status'] = 'active';

                    $success = 'Assinatura criada com sucesso (modo desenvolvimento, sem chamada ao Asaas).';
                } else {
                    // Fluxo real com Asaas em ambientes que não sejam development
                    // Cria/atualiza cliente no Asaas
                    $customer = AsaasClient::createOrUpdateCustomer($this->pdo, $userRow, $cpf, $phone);
                    $asaasCustomerId = $customer['id'] ?? null;

                    if (!$asaasCustomerId) {
                        throw new RuntimeException('ID de cliente Asaas não retornado.');
                    }

                    // Atualiza dados do usuário localmente
                    $upd = $this->pdo->prepare('UPDATE users SET full_name = :name, cpf = :cpf, phone = :phone, asaas_customer_id = :cust WHERE id = :id');
                    $upd->execute([
                        'name' => $fullName,
                        'cpf'  => $cpf,
                        'phone' => $phone,
                        'cust' => $asaasCustomerId,
                        'id'   => $userId,
                    ]);

                    // Cria assinatura mensal
                    $holderInfo = [
                        'name'  => $fullName,
                        'email' => $userRow['email'],
                        'cpf'   => $cpf,
                        'phone' => $phone,
                    ];
                    $cardData = [
                        'holder_name' => $cardHolder,
                        'number'      => $cardNumber,
                        'exp_month'   => $expMonth,
                        'exp_year'    => $expYear,
                        'cvv'         => $cardCvv,
                    ];

                    $subscription = AsaasClient::createSubscription($this->pdo, $asaasCustomerId, $holderInfo, $cardData, 2990);

                    $subscriptionId = $subscription['id'] ?? null;
                    $nextDueDate    = $subscription['nextDueDate'] ?? null;

                    if (!$subscriptionId) {
                        throw new RuntimeException('Assinatura não foi criada corretamente no Asaas.');
                    }

                    // Descobre ID do plano premium
                    $planStmt = $this->pdo->prepare("SELECT id FROM plans WHERE slug = 'premium' LIMIT 1");
                    $planStmt->execute();
                    $plan = $planStmt->fetch();
                    $premiumPlanId = $plan ? (int)$plan['id'] : null;

                    $upd = $this->pdo->prepare('UPDATE users SET asaas_subscription_id = :sub, plan_id = :plan_id, plan_status = :status, plan_activated_at = NOW(), plan_expires_at = :expires WHERE id = :id');
                    $upd->execute([
                        'sub'     => $subscriptionId,
                        'plan_id' => $premiumPlanId,
                        'status'  => 'active',
                        'expires' => $nextDueDate ?: null,
                        'id'      => $userId,
                    ]);

                    // Atualiza sessão também
                    $_SESSION['user']['full_name'] = $fullName;
                    $_SESSION['user']['plan_id'] = $premiumPlanId;
                    $_SESSION['user']['plan_status'] = 'active';

                    $success = 'Assinatura criada com sucesso! Seu plano agora é Premium.';
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        // Se nada foi definido até aqui, algo inesperado aconteceu
        if (!$error && !$success) {
            $error = 'Erro inesperado no fluxo de assinatura. Nenhuma mensagem foi gerada.';
        }

        // Armazena mensagens em flash e redireciona para a tela de configurações padrão
        if ($error) {
            $_SESSION['flash_error'] = $error;
        }
        if ($success) {
            $_SESSION['flash_success'] = $success;
        }

        header('Location: ' . BASE_URL . '?c=settings&a=index');
        exit;
    }
}
