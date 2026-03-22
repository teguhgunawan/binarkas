<?php

class DebtsController
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
                    'create' => $this->financeService->createDebt($_POST),
                    'update' => $this->financeService->updateDebt($_POST),
                    'delete' => $this->financeService->deleteDebt($_POST),
                    default => ['ok' => false, 'message' => 'Unsupported action.'],
                };

                $viewData['flash'] = $result;
            } catch (InvalidArgumentException $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => 'Please correct the debt form and try again.'];
                $viewData['debtFormErrors'] = json_decode($exception->getMessage(), true) ?: [];
                $viewData['debtFormData'] = [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'due' => (string) ($_POST['due'] ?? ''),
                    'amount' => (string) ($_POST['amount'] ?? '0'),
                    'status' => (string) ($_POST['status'] ?? 'safe'),
                ];
            } catch (Throwable $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => $exception->getMessage()];
            }
        }

        render('layout', $this->financeService->pageData('debts', $viewData));
    }
}
