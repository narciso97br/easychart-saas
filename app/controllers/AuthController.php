<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/config.php';

class AuthController
{
    public function login()
    {
        $error = '';

        if (!empty($_GET['msg']) && $_GET['msg'] === 'cpf_exists') {
            $error = 'CPF já cadastrado. Faça login para continuar.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = User::findByEmail($email);

            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                echo '<pre>';
                echo "DEBUG LOGIN\n";
                var_dump([
                    'email_enviado'      => $email,
                    'usuario_encontrado' => $user ? true : false,
                    'hash_no_banco'      => $user['password_hash'] ?? null,
                    'password_verify'    => $user ? password_verify($password, $user['password_hash']) : null,
                ]);
                echo '</pre>';
            }

            if ($user && password_verify($password, $user['password_hash'])) {
                // inicia sessão e redireciona para o dashboard
                $_SESSION['user'] = [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'role'      => $user['role'] ?? 'user',
                ];

                // Atualiza last_login_at para medir usuários ativos
                require_once __DIR__ . '/../core/Database.php';
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
                $stmt->execute(['id' => $user['id']]);

                header('Location: ' . BASE_URL . '?c=dashboard&a=index');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    public function register()
    {
        $error  = '';
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName        = trim($_POST['full_name'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $password        = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if ($password !== $passwordConfirm) {
                $error = 'Passwords do not match.';
            } elseif (User::findByEmail($email)) {
                $error = 'Email already registered.';
            } else {
                if (User::create($fullName, $email, $password, null)) {
                    $success = true;
                } else {
                    $error = 'Error creating account.';
                }
            }
        }

        require __DIR__ . '/../views/auth/register.php';
    }

    public function forgotPassword()
    {
        $error = '';
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email           = trim($_POST['email'] ?? '');
            $cpfRaw          = $_POST['cpf'] ?? '';
            $cpf             = preg_replace('/\D+/', '', $cpfRaw);
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($email === '' || $cpf === '') {
                $error = 'Informe o CPF e o e-mail.';
            } elseif ($newPassword === '' || $confirmPassword === '') {
                $error = 'Informe a nova senha e a confirmação.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'As senhas não conferem.';
            } else {
                $user = User::findByEmail($email);

                if (!$user) {
                    $error = 'Usuário não encontrado para o e-mail informado.';
                } elseif (empty($user['cpf']) || preg_replace('/\D+/', '', $user['cpf']) !== $cpf) {
                    $error = 'CPF não corresponde ao e-mail informado.';
                } else {
                    require_once __DIR__ . '/../core/Database.php';
                    $pdo = Database::getConnection();
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
                    $stmt->execute([
                        'hash' => $hash,
                        'id'   => $user['id'],
                    ]);

                    $success = true;
                }
            }
        }

        require __DIR__ . '/../views/auth/forgot_password.php';
    }
}

