<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Scheduling\AbstractJob;
use Illuminate\Support\Facades\Log;

class ExampleJob extends AbstractJob
{
    public function __construct()
    {
        parent::__construct();

        // Výchozí nastavení - spouštění každou hodinu
        $this->hourly();
        $this->description('Příklad jobu pro demonstraci');
    }


    protected function handle(): array
    {
        // Zde je vlastní implementace jobu
        Log::info('ExampleJob se spustil: ' . date('Y-m-d H:i:s'));

        // Vrací nějaký výsledek
        return ['status' => 'success', 'time' => time()];
    }
}
