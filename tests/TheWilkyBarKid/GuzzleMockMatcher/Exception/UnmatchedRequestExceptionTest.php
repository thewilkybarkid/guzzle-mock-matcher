<?php

namespace Guzzle\Tests\Plugin\Mock\Exception;

use PHPUnit_Framework_TestCase as TestCase;
use TheWilkyBarKid\GuzzleMockMatcher\Exception\UnmatchedRequestException;

class UnmatchedRequestExceptionTest extends TestCase
{
    public function testInstanceOf()
    {
        $exception = new UnmatchedRequestException();

        $this->assertInstanceOf('OutOfBoundsException', $exception);
    }
}
