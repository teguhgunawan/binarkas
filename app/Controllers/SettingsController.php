<?php

class SettingsController
{
    public function __construct(private readonly FinanceService $financeService, private readonly AuthService $authService)
    {
    }

    public function handle(string $page = 'settings'): void
    {
        $viewData = [
            'profileFormData' => [
                'full_name' => $this->authService->user()['full_name'] ?? '',
                'email' => $this->authService->user()['email'] ?? '',
                'current_password' => '',
                'new_password' => '',
                'confirm_password' => '',
            ],
            'categoryFormData' => ['name' => '', 'type' => 'expense', 'group_name' => 'expense', 'icon' => '', 'is_active' => '1', 'sort_order' => '10'],
            'formData' => ['name' => '', 'account_name' => '', 'type' => 'Bank', 'reference_number' => '', 'icon' => '', 'description' => '', 'is_active' => '1', 'balance' => '0'],
            'userFormData' => ['full_name' => '', 'email' => '', 'role' => 'user', 'password' => '', 'is_active' => '1'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $postedPage = (string) ($_POST['current_page'] ?? $page);
            if (in_array($postedPage, ['settings', 'user-management', 'account-management', 'category-management'], true)) {
                $page = $postedPage;
            }

            try {
                $result = match ($action) {
                    'update-profile' => $this->authService->updateCurrentUser($_POST),
                    'create-user' => $this->authService->createUser($_POST),
                    'update-user' => $this->authService->updateUser($_POST),
                    'delete-user' => $this->authService->deleteUser($_POST),
                    'create-category' => $this->financeService->createCategory($_POST),
                    'update-category' => $this->financeService->updateCategory($_POST),
                    'delete-category' => $this->financeService->deleteCategory($_POST),
                    'create-account' => $this->financeService->createAccount($_POST),
                    'update-account' => $this->financeService->updateAccount($_POST),
                    'delete-account' => $this->financeService->deleteAccount($_POST),
                    default => ['ok' => false, 'message' => 'Unsupported action.'],
                };
                $viewData['flash'] = $result;
            } catch (InvalidArgumentException $exception) {
                $errors = json_decode($exception->getMessage(), true) ?: [];
                $viewData['flash'] = ['ok' => false, 'message' => 'Please correct the setup form and try again.'];

                if ($action === 'update-profile') {
                    $viewData['profileFormErrors'] = $errors;
                    $viewData['profileFormData'] = [
                        'full_name' => (string) ($_POST['full_name'] ?? ''),
                        'email' => (string) ($_POST['email'] ?? ''),
                        'current_password' => '',
                        'new_password' => '',
                        'confirm_password' => '',
                    ];
                    $page = 'settings';
                } elseif (str_contains($action, 'user')) {
                    $viewData['userFormErrors'] = $errors;
                    $viewData['userFormData'] = [
                        'full_name' => (string) ($_POST['full_name'] ?? ''),
                        'email' => (string) ($_POST['email'] ?? ''),
                        'role' => (string) ($_POST['role'] ?? 'user'),
                        'password' => '',
                        'is_active' => (string) ($_POST['is_active'] ?? '0'),
                    ];
                    $page = 'user-management';
                } elseif (str_contains($action, 'category')) {
                    $viewData['categoryFormErrors'] = $errors;
                    $viewData['categoryFormData'] = [
                        'name' => (string) ($_POST['name'] ?? ''),
                        'type' => (string) ($_POST['type'] ?? 'expense'),
                        'group_name' => (string) ($_POST['group_name'] ?? 'expense'),
                        'icon' => (string) ($_POST['icon'] ?? ''),
                        'is_active' => (string) ($_POST['is_active'] ?? '0'),
                        'sort_order' => (string) ($_POST['sort_order'] ?? '10'),
                    ];
                    $page = 'category-management';
                } else {
                    $viewData['formErrors'] = $errors;
                    $viewData['formData'] = [
                        'name' => (string) ($_POST['name'] ?? ''),
                        'account_name' => (string) ($_POST['account_name'] ?? ''),
                        'type' => (string) ($_POST['type'] ?? 'Bank'),
                        'reference_number' => (string) ($_POST['reference_number'] ?? ''),
                        'icon' => (string) ($_POST['icon'] ?? ''),
                        'description' => (string) ($_POST['description'] ?? ''),
                        'is_active' => (string) ($_POST['is_active'] ?? '0'),
                        'balance' => (string) ($_POST['balance'] ?? '0'),
                    ];
                    $page = 'account-management';
                }
            } catch (Throwable $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => $exception->getMessage()];
            }
        }

        $viewData['users'] = $this->authService->allUsers();
        $viewData['authUser'] = $this->authService->user();
        render('layout', $this->financeService->pageData($page, $viewData));
    }
}
