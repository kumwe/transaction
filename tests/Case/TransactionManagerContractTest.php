<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Contract\TransactionManager;
use Kumwe\Transaction\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Pins the shape and the documented semantics of the transaction port.
 *
 * The port is an interface, so what it owns is a signature and a promise. Both are extracted from Kumwe
 * App unchanged, and both are asserted here so a rename, a widened parameter, a dropped generic or a
 * quietly weakened sentence fails the package rather than surprising an adapter.
 *
 * @since  0.1.0
 */
final class TransactionManagerContractTest extends TestCase
{
    /**
     * The port is an interface with exactly the three operations the App declared, and nothing else.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePortIsAnInterfaceWithExactlyThreeOperations(): void
    {
        $port = new ReflectionClass(TransactionManager::class);
        $names = array_map(static fn ($method): string => $method->getName(), $port->getMethods());
        sort($names);

        $this->assertTrue($port->isInterface(), 'The transaction boundary must be a contract, not a class.');
        $this->assertSame(['afterCommit', 'afterRollback', 'transactional'], $names, 'The operations are fixed.');
        $this->assertSame([], $port->getInterfaceNames(), 'The port extends no other interface.');
        $this->assertSame([], $port->getConstants(), 'The port declares no constant.');
        $this->assertSame('Kumwe\\Transaction\\Contract', $port->getNamespaceName(), 'The canonical namespace.');
    }

    /**
     * Every operation takes one required callable named `$operation` and returns what the App promised.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testEveryOperationTakesOneCallableAndReturnsTheDeclaredType(): void
    {
        $returns = ['transactional' => 'mixed', 'afterCommit' => 'void', 'afterRollback' => 'void'];
        foreach ((new ReflectionClass(TransactionManager::class))->getMethods() as $method) {
            $parameters = $method->getParameters();
            $this->assertFalse($method->isStatic(), $method->getName() . '() is an instance operation.');
            $this->assertSame(1, count($parameters), $method->getName() . '() takes exactly one argument.');
            $parameter = $parameters[0];
            $type = $parameter->getType();
            $this->assertSame('operation', $parameter->getName(), 'The argument is the operation.');
            $this->assertTrue($type instanceof ReflectionNamedType, 'The operation is natively typed.');
            $this->assertSame('callable', $type instanceof ReflectionNamedType ? $type->getName() : null, 'callable');
            $this->assertFalse($parameter->isOptional(), 'The operation is required.');
            $this->assertFalse($parameter->isVariadic(), 'One operation per call.');
            $this->assertFalse($parameter->isPassedByReference(), 'The operation is passed by value.');
            $return = $method->getReturnType();
            $this->assertSame(
                $returns[$method->getName()] ?? null,
                $return instanceof ReflectionNamedType ? $return->getName() : null,
                $method->getName() . '() keeps its declared return type.',
            );
        }
    }

    /**
     * The generic return contract of `transactional()` is load-bearing for static analysis and is documented.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheGenericReturnContractIsDocumented(): void
    {
        $port = new ReflectionClass(TransactionManager::class);
        $transactional = (string) $port->getMethod('transactional')->getDocComment();
        $commit = (string) $port->getMethod('afterCommit')->getDocComment();
        $rollback = (string) $port->getMethod('afterRollback')->getDocComment();

        $this->assertStringContains('@template T', $transactional, 'transactional() is generic over T.');
        $this->assertStringContains('@param   callable(): T  $operation', $transactional, 'The operation yields T.');
        $this->assertStringContains('@return  T', $transactional, 'The operation result is returned as T.');
        $this->assertStringContains('callable(): void  $operation', $commit, 'A commit hook returns nothing.');
        $this->assertStringContains('callable(): void  $operation', $rollback, 'A rollback hook returns nothing.');
    }

    /**
     * The semantics an adapter owes are stated in the contract's own documentation, sentence by sentence.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheContractStatesTheSemanticsAnAdapterOwes(): void
    {
        $port = new ReflectionClass(TransactionManager::class);
        $summary = (string) $port->getDocComment();
        $transactional = (string) $port->getMethod('transactional')->getDocComment();
        $commit = (string) $port->getMethod('afterCommit')->getDocComment();
        $rollback = (string) $port->getMethod('afterRollback')->getDocComment();

        $this->assertStringContains('nesting is invisible to the caller', $summary, 'Nesting rule.');
        $this->assertStringContains('waits for the outermost commit', $summary, 'Commit-hook deferral rule.');
        $this->assertStringContains('as soon as the scope that registered it is discarded', $summary, 'Rollback rule.');
        $this->assertStringContains('A nested call joins the enclosing scope', $transactional, 'Join rule.');
        $this->assertStringContains('only the outermost call commits', $transactional, 'Outermost commit rule.');
        $this->assertStringContains('reaches the caller unchanged', $transactional, 'Exception propagation rule.');
        $this->assertStringContains('immediately when no transaction is active', $commit, 'Inline commit rule.');
        $this->assertStringContains('Registering while no transaction is active is a no-op', $rollback, 'No-op rule.');
    }
}
