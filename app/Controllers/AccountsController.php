<?php

class AccountsController
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function handle(): void
    {
        $input = [
            'account' => (string) ($_GET['account'] ?? '__all'),
            'date_from' => (string) ($_GET['date_from'] ?? date('Y-m-01')),
            'date_to' => (string) ($_GET['date_to'] ?? date('Y-m-d')),
            'keyword' => (string) ($_GET['keyword'] ?? ''),
        ];

        $ledger = $this->financeService->buildAccountLedger($input);
        render('layout', $this->financeService->pageData('accounts', [
            'accountLedgerFilters' => $ledger['filters'],
            'accountLedgerRows' => $ledger['rows'],
            'accountLedgerGroupedRows' => $ledger['groupedRows'],
            'selectedAccountLabel' => $ledger['selectedAccountLabel'],
        ]));
    }
}
