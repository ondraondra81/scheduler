<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Scheduler\Contract\Command as CommandInterface;
use Closure;

class Command implements CommandInterface
{
    /**
     * @var array<string>
     */
    private array $parameters = [];

    public function __construct(
        private readonly string|Closure $execute,
    ) {
    }


    /**
     * @param array<string> $parameters
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters =  $parameters;

        return $this;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }


    public function execute(): string|Closure
    {
        return $this->execute;
    }
}
