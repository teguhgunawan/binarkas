<?php

class AccountsController
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function handle(): void
    {
        $viewData = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            try {
                $result = match ($action) {
                    'create-transaction' => $this->financeService->createTransaction($_POST),
                    'update-transaction' => $this->financeService->updateTransaction($_POST),
                    'delete-transaction' => $this->financeService->deleteTransaction($_POST),
                    default => ['ok' => false, 'message' => 'Unsupported account action.'],
                };
                $viewData['flash'] = $result;
            } catch (InvalidArgumentException $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => 'Form transaksi tidak valid.'];
            } catch (Throwable $exception) {
                $viewData['flash'] = ['ok' => false, 'message' => $exception->getMessage()];
            }
        }

        $source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $input = [
            'account' => (string) ($source['account'] ?? '__all'),
            'date_from' => (string) ($source['date_from'] ?? date('Y-m-01')),
            'date_to' => (string) ($source['date_to'] ?? date('Y-m-d')),
            'keyword' => (string) ($source['keyword'] ?? ''),
            'book_id' => (string) ($source['book_id'] ?? ''),
        ];

        $ledger = $this->financeService->buildAccountLedger($input);
        render('layout', $this->financeService->pageData('accounts', array_merge($viewData, [
            'accountLedgerFilters' => $ledger['filters'],
            'accountLedgerRows' => $ledger['rows'],
            'accountLedgerGroupedRows' => $ledger['groupedRows'],
            'selectedAccountLabel' => $ledger['selectedAccountLabel'],
        ])));
    }
}
