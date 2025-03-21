<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-task {foo=foo} {bar=bar} {--option} {--queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testovaci task pro scheduler ArtisanCommand';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $foo = $this->argument('foo');
        $bar = $this->argument('bar');
        $options = $this->options();

        $string = '';
        foreach ($options as $key => $value) {
            $string .= $key . ': ' . ($value ? 'true' : 'false') . ' ';
        }
        $this->output->writeln(
            sprintf(
                'Spustil jsem test Test task v: %s s parametry: foo: "%s", bar: "%s", options: "%s"',
                (new \DateTime())->format('Y-m-d H:i:s'),
                $foo,
                $bar,
                $string
            )
        );
    }
}
