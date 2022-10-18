<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Psr\Http\Message\{MessageInterface, RequestInterface, UriInterface, StreamInterface};
use Dominicus75\Psr7\{AbstractMessage, Request, Uri, Stream};
use PHPUnit\Framework\TestCase;

/**
 * @covers Dominicus75\Psr7\Request
 * Based on Tobias Nyholm's work and Guzzle
 * @see https://github.com/Nyholm/psr7/blob/master/tests/RequestTest.php
 * @see https://github.com/guzzle/psr7/blob/master/tests/RequestTest.php
 */
class RequestTest extends TestCase
{
    private Request $request;

    public function testRequestUriMayBeString()
    {
        $r = new Request('GET', '/');
        $this->assertEquals('/', (string) $r->getUri());
    }

    public function testRequestUriMayBeUri()
    {
        $uri = new Uri('/');
        $r = new Request('GET', $uri);
        $this->assertSame($uri, $r->getUri());
    }

    public function testValidateRequestUri()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("/// is not a valid URI");

        new Request('GET', '///');
    }

    public function testCanConstructWithBody()
    {
        $r = new Request(method: 'GET', uri: '/', headers: [], body: 'baz');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertEquals('baz', (string) $r->getBody());
    }

    public function testEmptyBody()
    {
        $r = new Request(method: 'GET', uri: '/', body: '');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('', (string) $r->getBody());
    }

    public function testFalseyBody()
    {
        $r = new Request(method: 'GET', uri: '/', body: "0");
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('0', (string) $r->getBody());
    }

    public function testNullBody()
    {
        $this->expectException(\TypeError::class);
        $r = new Request(method: 'GET', uri: '/', body: null);
    }

    public function testWithUri()
    {
        $r1 = new Request('GET', '/');
        $u1 = $r1->getUri();
        $u2 = new Uri('http://www.example.com');
        $r2 = $r1->withUri($u2);
        $this->assertNotSame($r1, $r2);
        $this->assertSame($u2, $r2->getUri());
        $this->assertSame($u1, $r1->getUri());

        $r3 = new Request('GET', '/');
        $u3 = $r3->getUri();
        $r4 = $r3->withUri($u3);
        $this->assertSame($r3, $r4, 'If the Request did not change, then there is no need to create a new request object');

        $u4 = new Uri('/');
        $r5 = $r3->withUri($u4);
        $this->assertNotSame($r3, $r5);
    }

    public function testSameInstanceWhenSameUri()
    {
        $r1 = new Request('GET', 'http://foo.com');
        $r2 = $r1->withUri($r1->getUri());
        $this->assertSame($r1, $r2);
    }

    public function testWithRequestTarget()
    {
        $r1 = new Request('GET', '/');
        $r2 = $r1->withRequestTarget('*');
        $this->assertEquals('*', $r2->getRequestTarget());
        $this->assertEquals('/', $r1->getRequestTarget());
    }

    public function testWithInvalidRequestTarget()
    {
        $this->expectException(\InvalidArgumentException::class);
        $r = new Request('GET', '/');
        $r->withRequestTarget('foo bar');
    }
}
