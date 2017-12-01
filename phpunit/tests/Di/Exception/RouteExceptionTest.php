<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Core\Http\Exception\RouteException;

/**
 * @covers \Core\Http\Di\Exception\DiException
 */
class RouteExceptionTest extends TestCase
{
    public function testFabricObject()
    {
        $this->assertTrue(new RouteException('') instanceof \Exception, 'Check instanceof');

    }
}
