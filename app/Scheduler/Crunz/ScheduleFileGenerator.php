<?php

declare(strict_types=1);

namespace App\Scheduler\Crunz;

use App\Scheduler\Contract\Task;
use App\Scheduler\Exception\SchedulerException;
use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ReflectionException;
use ReflectionFunction;

class ScheduleFileGenerator
{
    public function generateFileContent(Task $task): string
    {
        $command = $task->command();
        $event = $task->event();

        $closures = [];

        if (!is_string($command->execute())) {
            $closures[] = $command->execute();
        }

        $closures = array_merge(
            $closures,
            $event->runConditions(),
            $task->beforeCallbacks(),
            $task->afterCallbacks(),
            $task->successCallbacks(),
            $task->failureCallbacks()
        );

        $allUseStatements = [];
        $usesIlluminate = false;

        foreach ($closures as $closure) {
            foreach ($this->extractUseStatements($closure) as $stmt) {
                $allUseStatements[$stmt] = true;

                if (str_contains($stmt, 'Illuminate\\Support\\Facades')) {
                    $usesIlluminate = true;
                }
            }
        }

        $content = "<?php\n";
        $content .= "// Crunz task generovaný automaticky\n\n";

        if ($usesIlluminate) {
            $content .= "require_once __DIR__.'/../../vendor/autoload.php';\n";
            $content .= "\$app = require_once __DIR__.'/../../bootstrap/app.php';\n";
            $content .= "\n\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();\n\n";
        }

        $content .= "use Crunz\\Schedule;\n";
        if (!empty($allUseStatements)) {
            $content .= implode("\n", array_keys($allUseStatements)) . "\n";
        }
        $content .= "\n\$schedule = new Schedule();\n\n";

        $content .= '// Description: ' . $task->getDescription() . "\n";

        $rawExecute = $command->execute();
        $isClosure = $rawExecute instanceof \Closure;

        $execute = $isClosure
            ? $this->wrapClosureWithArguments($rawExecute, $command->parameters())
            : $this->buildExecuteExpr($rawExecute);

        $content .= $this->buildScheduleRunStatement($execute, $isClosure ? [] : $command->parameters());

        $content .= sprintf("\$task->cron('%s');\n", $event->getExpression());

        if ($task->getDescription()) {
            $content .= sprintf("\$task->description('%s');\n", addslashes($task->getDescription()));
        }

        foreach ($event->runConditions() as $callback) {
            $content .= $this->generateCallbackCode('$task->when', $this->closureToString($this->parseClosureToExpr($callback)));
        }

        foreach ($task->beforeCallbacks() as $callback) {
            $content .= $this->generateCallbackCode('$task->before', $this->closureToString($this->parseClosureToExpr($callback)));
        }

        foreach ($task->successCallbacks() as $callback) {
            $content .= $this->generateCallbackCode('$task->after', $this->closureToString($this->parseClosureToExpr($callback)));
        }

        foreach ($task->afterCallbacks() as $callback) {
            $content .= $this->generateCallbackCode('$task->after', $this->closureToString($this->parseClosureToExpr($callback)));
        }

        foreach ($task->failureCallbacks() as $callback) {
            $content .= $this->generateCallbackCode('$schedule->onError', $this->closureToString($this->parseClosureToExpr($callback)));
        }

        if ($task->shouldRunOnOneServer()) {
            $content .= "\$task->preventOverlapping();\n";
        }

        $content .= "\nreturn \$schedule;\n";

        return $content;
    }

    private function buildExecuteExpr(Closure|string $execute): Expr
    {
        if (is_string($execute)) {
            if (preg_match('/^[a-z0-9:_-]+$/i', $execute)) {
                return new Expr\BinaryOp\Concat(
                    new Expr\ConstFetch(new Node\Name('PHP_BINARY')),
                    new Node\Scalar\String_(' artisan ' . $execute)
                );
            }

            if (str_starts_with($execute, PHP_BINARY . ' ')) {
                $remainder = substr($execute, strlen(PHP_BINARY . ' '));
                return new Expr\BinaryOp\Concat(
                    new Expr\ConstFetch(new Node\Name('PHP_BINARY')),
                    new Node\Scalar\String_(' ' . $remainder)
                );
            }

            return new Node\Scalar\String_($execute);
        }

        return $this->parseClosureToExpr($execute);
    }


    private function parseClosureToExpr(Closure $closure): Expr\Closure
    {
        $reflection = new \ReflectionFunction($closure);
        $file = $reflection->getFileName();

        if ($file === false) {
            throw new SchedulerException('Unable to determine file name of closure.');
        }

        $source = file_get_contents($file);

        if ($source === false) {
            throw new SchedulerException('Cannot read file: ' . $file);
        }

        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($source);

        if ($ast === null) {
            throw new SchedulerException('Unable to parse PHP file.');
        }

        $startLine = $reflection->getStartLine();
        if ($startLine === false) {
            throw new SchedulerException('Unable to reflect start line of closure.');
        }

        $traverser = new NodeTraverser();
        $visitor = new class ($startLine) extends NodeVisitorAbstract {
            private int $startLine;
            public ?Expr\Closure $closureNode = null;

            public function __construct(int $startLine)
            {
                $this->startLine = $startLine;
            }

            public function enterNode(Node $node): int|null
            {
                if ($node instanceof Expr\Closure && $node->getStartLine() === $this->startLine) {
                    $this->closureNode = $node;
                    return NodeVisitor::STOP_TRAVERSAL;
                }

                return null;
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        if (!$visitor->closureNode instanceof Expr\Closure) {
            throw new SchedulerException('Closure was not found.');
        }

        return $visitor->closureNode;
    }


    /**
     * @return array<int, string>
     * @throws ReflectionException
     */
    private function extractUseStatements(Closure $closure): array
    {
        $reflection = new ReflectionFunction($closure);
        $file = $reflection->getFileName();
        if ($file === false) {
            throw new SchedulerException('Unable to determine file name of closure.');
        }

        $source = file_get_contents($file);
        if ($source === false) {
            throw new SchedulerException('Unable to read file content.');
        }

        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($source);
        if ($ast === null) {
            throw new SchedulerException('Unable to parse PHP file.');
        }
        /** @var array<string, bool> $useStatements */
        $useStatements = [];

        $closureCode = $this->closureToString($this->parseClosureToExpr($closure));

        $traverser = new NodeTraverser();
        $visitor = new class extends NodeVisitorAbstract {
            /** @var array<int, Node\Stmt\Use_> $useStatements */
            public array $useStatements = [];

            public function enterNode(Node $node): null
            {
                if ($node instanceof Node\Stmt\Use_) {
                    $this->useStatements[] = $node;
                }

                return null;
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $printer = new Standard();

        foreach ($visitor->useStatements as $stmt) {
            foreach ($stmt->uses as $useUse) {
                $alias = $useUse->alias ? $useUse->alias->toString() : $useUse->name->getLast();

                if (preg_match('~(?<![\\\\\\w])' . preg_quote($alias, '~') . '(?![\\\\\\w])~', $closureCode)) {
                    $useStatements[$printer->prettyPrint([$stmt]) . ';'] = true;
                }
            }
        }

        return array_keys($useStatements);
    }


    /**
     * @param array<string|int, mixed> $parameters
     */
    private function buildScheduleRunStatement(Expr $executeExpr, array $parameters): string
    {
        $prettyPrinter = new Standard();

        $methodCall = new Expr\MethodCall(
            new Expr\Variable('schedule'),
            'run',
            [
                new Node\Arg($executeExpr),
                new Node\Arg($this->buildArrayNode($parameters)),
            ]
        );

        $assignment = new Expr\Assign(
            new Expr\Variable('task'),
            $methodCall
        );

        return $prettyPrinter->prettyPrint([$assignment]) . ';' . "\n";
    }


    /**
     * @param array<string|int, mixed> $array
     */
    private function buildArrayNode(array $array): Expr\Array_
    {
        $items = [];

        foreach ($array as $key => $value) {
            $keyNode = is_int($key) ? null : new Node\Scalar\String_($key);
            $valueNode = $this->convertValueToNode($value);
            $items[] = new Expr\ArrayItem($valueNode, $keyNode);
        }

        return new Expr\Array_($items, ['kind' => Expr\Array_::KIND_SHORT]);
    }

    private function convertValueToNode(mixed $value): Expr
    {
        if (is_string($value)) {
            return new Node\Scalar\String_($value);
        }

        if (is_int($value)) {
            return new Node\Scalar\LNumber($value);
        }

        if (is_float($value)) {
            return new Node\Scalar\DNumber($value);
        }

        if (is_bool($value)) {
            return new Expr\ConstFetch(new Node\Name($value ? 'true' : 'false'));
        }

        if (is_null($value)) {
            return new Expr\ConstFetch(new Node\Name('null'));
        }

        if (is_array($value)) {
            return $this->buildArrayNode($value);
        }

        return new Expr\ConstFetch(new Node\Name('null'));
    }

    private function generateCallbackCode(string $method, string $closure): string
    {
        return sprintf("%s(%s);\n", $method, $closure);
    }

    private function closureToString(Expr\Closure $node): string
    {
        return (new Standard())->prettyPrint([$node]);
    }

    /**
     * @param array<string|int, mixed> $parameters
     */
    private function wrapClosureWithArguments(\Closure $closure, array $parameters): Expr\Closure
    {
        $innerClosure = $this->parseClosureToExpr($closure);

        $wrappedCall = new Expr\FuncCall(
            $innerClosure,
            array_map(fn($v) => new Node\Arg($this->convertValueToNode($v)), $parameters)
        );

        $wrappedStatement = new Node\Stmt\Expression($wrappedCall);

        return new Expr\Closure([
            'static' => $innerClosure->static,
            'uses' => [],
            'stmts' => [ $wrappedStatement ],
        ]);
    }
}
