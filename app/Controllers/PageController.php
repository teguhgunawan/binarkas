<?php

class PageController
{
    public function __construct(private readonly FinanceService $financeService)
    {
    }

    public function show(string $page, array $extra = []): void
    {
        render('layout', $this->financeService->pageData($page, $extra));
    }
}
