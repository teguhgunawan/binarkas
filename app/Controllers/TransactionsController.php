<?php

class TransactionsController
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
                    'create' => $this->financeService->createTransaction($_POST),
                    'update' => $this->financeService->updateTransaction($_POST),
                    'delete' => $this->financeService->deleteTransaction($_POST),
                    default => ['ok' => false, 'message' => 'Unsupported action.'],
                };

                $viewData['flash'] = $result;
            } catch (InvalidArgumentException $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => 'Please correct the transaction form and try again.'];
                $viewData['transactionFormErrors'] = json_decode($exception->getMessage(), true) ?: [];
                $viewData['transactionFormData'] = [
                    'date' => (string) ($_POST['date'] ?? date('Y-m-d')),
                    'title' => (string) ($_POST['title'] ?? ''),
                    'category' => (string) ($_POST['category'] ?? ''),
                    'type' => (string) ($_POST['type'] ?? 'expense'),
                    'amount' => (string) ($_POST['amount'] ?? '0'),
                ];
            } catch (Throwable $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => $exception->getMessage()];
            }
        }

        render('layout', $this->financeService->pageData('transactions', $viewData));
    }
}
