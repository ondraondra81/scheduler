<?php

declare(strict_types=1);

namespace App\Providers;

use App\Scheduler\Console\Commands\SchedulerRun;
use App\Scheduler\Contract\Scheduler;
use App\Scheduler\Crunz\ArtisanScheduler;
use App\Scheduler\Crunz\BootHandler\ArtisanBootHandler;
use App\Scheduler\Crunz\BootHandler\FileBootHandler;
use App\Scheduler\Crunz\Commands\CrunzScheduleList;
use App\Scheduler\Crunz\Commands\CrunzScheduleRun;
use App\Scheduler\Crunz\Contract\BootHandler;
use App\Scheduler\Crunz\CrunzScheduleFactory;
use App\Scheduler\Crunz\FileScheduler;
use App\Scheduler\Crunz\ScheduleCollection;
use App\Scheduler\Crunz\ScheduleFileGenerator;
use App\Scheduler\Exception\SchedulerException;
use App\Scheduler\Task;
use App\Scheduler\TaskLoader;
use Crunz\Application\Service\ConfigurationInterface;
use Crunz\Clock\Clock;
use Crunz\Clock\ClockInterface;
use Crunz\Configuration\Configuration;
use Crunz\Configuration\ConfigurationParser;
use Crunz\Configuration\ConfigurationParserInterface;
use Crunz\Configuration\Definition;
use Crunz\Configuration\FileParser;
use Crunz\EventRunner;
use Crunz\Filesystem\Filesystem;
use Crunz\Filesystem\FilesystemInterface;
use Crunz\HttpClient\CurlHttpClient;
use Crunz\HttpClient\HttpClientInterface;
use Crunz\Invoker;
use Crunz\Logger\ConsoleLogger;
use Crunz\Logger\ConsoleLoggerInterface;
use Crunz\Logger\LoggerFactory;
use Crunz\Mailer;
use Crunz\Output\OutputFactory;
use Crunz\Task\Timezone;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class CruzSchedulerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->singleton(
            TaskLoader::class,
            function () {
                $taskDirectory = config('scheduler.task_directory');
                if (!is_string($taskDirectory)) {
                    throw new SchedulerException('Task directory must be a string.');
                }
                if (!is_dir($taskDirectory)) {
                    throw new SchedulerException('Task directory does not exist.');
                }

                return new TaskLoader(
                    $taskDirectory
                );
            }
        );

        $this->app->singleton(
            ScheduleCollection::class,
            function () {
                return new ScheduleCollection();
            }
        );

        $this->registerCrunzDepedency();
        $this->registerAdapterDepedency();



        $this->commands([
            SchedulerRun::class,
            CrunzScheduleRun::class,
            CrunzScheduleList::class,
        ]);
    }

    public function boot(BootHandler $bootHandler): void
    {
        $bootHandler->boot();
    }

    private function registerAdapterDepedency(): void
    {
        $type = config('scheduler.crunz.type', 'artisan');
        if ($type === 'artisan') {
            $this->app->singleton(
                Scheduler::class,
                function (Application $app) {

                    return new ArtisanScheduler(
                        $app->get(ScheduleCollection::class),
                        $app->make(CrunzScheduleFactory::class),
                        $app->make(Kernel::class),
                    );
                }
            );

            $this->app->singleton(
                BootHandler::class,
                function (Application $app) {
                    return new ArtisanBootHandler(
                        $this->app->make(TaskLoader::class),
                        $app->make(Scheduler::class)
                    );
                }
            );

            return;
        }

        if ($type === 'file') {
            $originalTaskDir = config('scheduler.task_directory');
            if (!is_string($originalTaskDir)) {
                throw new SchedulerException('Task directory must be a string.');
            }
            $crunzTaskDir = config('scheduler.crunz_task_directory', storage_path('crunz'));
            if (!is_string($crunzTaskDir)) {
                throw new SchedulerException('Crunz task directory must be a string.');
            }

            $this->app->singleton(
                Scheduler::class,
                function (Application $app) use ($crunzTaskDir) {
                    return new FileScheduler(
                        $app->get(ScheduleFileGenerator::class),
                        $crunzTaskDir,
                    );
                }
            );

            $this->app->singleton(
                BootHandler::class,
                function (Application $app) use ($originalTaskDir, $crunzTaskDir) {
                    return new FileBootHandler(
                        $app->get(TaskLoader::class),
                        $app->get(FileScheduler::class),
                        $app->get('cache'), //@phpstan-ignore-line
                        $app->get('files'), //@phpstan-ignore-line
                        $originalTaskDir,
                        $crunzTaskDir,
                    );
                }
            );
        }

        throw new SchedulerException('Unsupported crunz scheduler type.');
    }

    /**
     * @return void
     */
    private function registerCrunzDepedency(): void
    {
        $this->app->singleton(FilesystemInterface::class, function ($app) {
            return new Filesystem();
        });

        $this->app->singleton(InputInterface::class, function () {
            return new ArgvInput();
        });

        $this->app->singleton(OutputInterface::class, function ($app) {
            $input = $app->make(InputInterface::class);
            $factory = new OutputFactory($input);
            return $factory->createOutput();
        });

        $this->app->singleton(ConsoleLoggerInterface::class, function ($app) {
            $input = $app->make(InputInterface::class);
            $output = $app->make(OutputInterface::class);
            return new ConsoleLogger(
                new SymfonyStyle($input, $output)
            );
        });

        $this->app->singleton(ConfigurationParserInterface::class, function ($app) {
            return new ConfigurationParser(
                new Definition(),
                new Processor(),
                new FileParser(new Yaml()),
                $app->make(ConsoleLoggerInterface::class),
                $app->make(FilesystemInterface::class)
            );
        });

        $this->app->singleton(ConfigurationInterface::class, function ($app) {
            return $app->make(Configuration::class);
        });

        $this->app->singleton(Configuration::class, function ($app) {
            return new Configuration(
                $app->make(ConfigurationParserInterface::class),
                $app->make(Filesystem::class)  // Odstraněna čárka
            );
        });

        $this->app->singleton(Invoker::class, function ($app) {
            return new Invoker();
        });

        $this->app->singleton(Mailer::class, function ($app) {
            return new Mailer(
                $app->make(ConfigurationInterface::class)
            );
        });

        $this->app->singleton(ClockInterface::class, function ($app) {
            return new Clock();
        });

        $this->app->singleton(Timezone::class, function ($app) {
            return new Timezone(
                $app->make(ConfigurationInterface::class),
                $app->make(ConsoleLoggerInterface::class)
            );
        });

        $this->app->singleton(LoggerFactory::class, function ($app) {
            return new LoggerFactory(
                $app->make(ConfigurationInterface::class),
                $app->make(Timezone::class),
                $app->make(ConsoleLoggerInterface::class),
                $app->make(ClockInterface::class)
            );
        });

        $this->app->singleton(HttpClientInterface::class, function ($app) {
            return new CurlHttpClient();
        });

        $this->app->singleton(EventRunner::class, function ($app) {
            return new EventRunner(
                $app->make(Invoker::class),
                $app->make(ConfigurationInterface::class),
                $app->make(Mailer::class),
                $app->make(LoggerFactory::class),
                $app->make(HttpClientInterface::class),
                $app->make(ConsoleLoggerInterface::class)
            );
        });
    }
}
