<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Contract\TransactionState;
use Kumwe\Transaction\Tests\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Pins the shape and the documented promise of the transaction-state port.
 *
 * @since  0.1.0
 */
final class TransactionStateContractTest extends TestCase
{
    /**
     * The port is an interface with exactly one boolean query and no argument.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePortIsAnInterfaceWithOneBooleanQuery(): void
    {
        $port = new ReflectionClass(TransactionState::class);
        $methods = $port->getMethods();

        $this->assertTrue($port->isInterface(), 'The state view must be a contract, not a class.');
        $this->assertSame(1, count($methods), 'The port answers exactly one question.');
        $method = $methods[0];
        $return = $method->getReturnType();
        $this->assertSame('isActive', $method->getName(), 'The question is whether a transaction is active.');
        $this->assertSame([], $method->getParameters(), 'The question takes no argument.');
        $this->assertFalse($method->isStatic(), 'The question is asked of an instance bound to a connection.');
        $this->assertSame('bool', $return instanceof ReflectionNamedType ? $return->getName() : null, 'bool');
        $this->assertSame([], $port->getInterfaceNames(), 'The port extends no other interface.');
        $this->assertSame([], $port->getConstants(), 'The port declares no constant.');
    }

    /**
     * The promise covers every nesting level and every route that can open a transaction.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePromiseCoversEveryNestingLevelAndEveryRoute(): void
    {
        $port = new ReflectionClass(TransactionState::class);
        $method = (string) $port->getMethod('isActive')->getDocComment();

        $this->assertStringContains('however deeply nested', $method, 'Any nesting level counts as active.');
        $this->assertStringContains('not who opened it', $method, 'The route that opened it is irrelevant.');
        $this->assertStringContains(
            'without handing the application layer a connection',
            (string) $port->getDocComment(),
            'No connection leaks.',
        );
    }
}
