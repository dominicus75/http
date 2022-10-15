<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Psr\Http\Message\{MessageInterface, RequestInterface, UriInterface, StreamInterface};
use Dominicus75\Psr7\{AbstractMessage, Request, Uri, Stream};
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
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

}
