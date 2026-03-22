<?php

class AuthController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(): void
    {
        $flash = null;
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = (string) ($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $result = $this->authService->attemptLogin($email, $password);

            if (!empty($result['ok'])) {
                header('Location: ' . app_base('?page=dashboard'));
                exit;
            }

            $flash = $result;
        }

        render('auth/login', [
            'config' => config('app'),
            'flash' => $flash,
            'email' => $email,
        ]);
    }

    public function logout(): void
    {
        $this->authService->logout();
        header('Location: ' . app_base('?page=login'));
        exit;
    }
}
