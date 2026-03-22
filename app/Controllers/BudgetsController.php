<?php

class BudgetsController
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function handle(): void
    {
        $viewData = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            try {
                $result = match ($action) {
                    'create' => $this->financeService->createBudget($_POST),
                    'update' => $this->financeService->updateBudget($_POST),
                    'delete' => $this->financeService->deleteBudget($_POST),
                    default => ['ok' => false, 'message' => 'Unsupported action.'],
                };

                $viewData['flash'] = $result;
            } catch (InvalidArgumentException $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => 'Please correct the budget form and try again.'];
                $viewData['budgetFormErrors'] = json_decode($exception->getMessage(), true) ?: [];
                $viewData['budgetFormData'] = [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'allocated' => (string) ($_POST['allocated'] ?? '0'),
                    'used' => (string) ($_POST['used'] ?? '0'),
                ];
            } catch (Throwable $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => $exception->getMessage()];
            }
        }

        render('layout', $this->financeService->pageData('budgets', $viewData));
    }
}
