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
                    'name'               => (string) ($_POST['name'] ?? ''),
                    'debt_type'          => (string) ($_POST['debt_type'] ?? 'credit_card'),
                    'due'                => (string) ($_POST['due'] ?? ''),
                    'amount'             => (string) ($_POST['amount'] ?? '0'),
                    'status'             => (string) ($_POST['status'] ?? 'safe'),
                    'cc_last_four'       => (string) ($_POST['cc_last_four'] ?? ''),
                    'cc_expiry'          => (string) ($_POST['cc_expiry'] ?? ''),
                    'cc_billing_day'     => (string) ($_POST['cc_billing_day'] ?? ''),
                    'cc_due_day'         => (string) ($_POST['cc_due_day'] ?? ''),
                    'loan_principal'     => (string) ($_POST['loan_principal'] ?? '0'),
                    'loan_installment'   => (string) ($_POST['loan_installment'] ?? '0'),
                    'loan_tenure_months' => (string) ($_POST['loan_tenure_months'] ?? ''),
                    'loan_paid_months'   => (string) ($_POST['loan_paid_months'] ?? '0'),
                    'loan_due_day'       => (string) ($_POST['loan_due_day'] ?? ''),
                    'interest_rate'      => (string) ($_POST['interest_rate'] ?? ''),
                    'od_usage_start_date' => (string) ($_POST['od_usage_start_date'] ?? ''),
                    'od_due_date'         => (string) ($_POST['od_due_date'] ?? ''),
                ];
            } catch (Throwable $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => $exception->getMessage()];
            }
        }

        render('layout', $this->financeService->pageData('debts', $viewData));
    }
}
