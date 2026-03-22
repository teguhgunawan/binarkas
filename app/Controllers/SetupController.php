<?php

class SetupController
{
    public function __construct(private readonly DatabaseSetupService $setupService)
    {
    }

    public function handle(): void
    {
        $flash = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run-migrations') {
            $flash = $this->setupService->runMigrations();
        }

        render('setup', [
            'config' => config('app'),
            'status' => $this->setupService->status(),
            'flash' => $flash,
        ]);
    }
}
