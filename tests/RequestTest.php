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

    public function testGetRequestTarget()
    {
        $r = new Request('GET', 'https://nyholm.tech');
        $this->assertEquals('/', $r->getRequestTarget());

        $r = new Request('GET', 'https://nyholm.tech/foo?bar=baz');
        $this->assertEquals('/foo?bar=baz', $r->getRequestTarget());

        $r = new Request('GET', 'https://nyholm.tech?bar=baz');
        $this->assertEquals('/?bar=baz', $r->getRequestTarget());
    }

    public function testCapitalizesMethod(): void
    {
        $r = new Request('gEt', '/');
        self::assertSame('GET', $r->getMethod());
    }

    public function testCapitalizesWithMethod(): void
    {
        $r = new Request('GeT', '/');
        self::assertSame('PUT', $r->withMethod('put')->getMethod());
    }

    /**
     * @dataProvider invalidMethodsProvider
     */
    public function testWithInvalidMethodTypes($method): void
    {
        $r = new Request('get', '/');
        $this->expectException(\TypeError::class);
        $r->withMethod($method);
    }

    public function invalidMethodsProvider(): iterable
    {
        return [
            [null],
            [false],
            [['foo']],
            [new \stdClass()],
        ];
    }

    public function testRequestTargetDoesNotAllowSpaces(): void
    {
        $r1 = new Request('GET', '/');
        $this->expectException(\InvalidArgumentException::class);
        $r1->withRequestTarget('/foo bar');
    }

    public function testRequestTargetDefaultsToSlash(): void
    {
        $r1 = new Request('GET', '');
        self::assertSame('/', $r1->getRequestTarget());
        $r2 = new Request('GET', '*');
        self::assertSame('*', $r2->getRequestTarget());
        $r3 = new Request('GET', 'http://foo.com/bar baz/');
        self::assertSame('/bar%20baz/', $r3->getRequestTarget());
    }

    public function testBuildsRequestTarget(): void
    {
        $r1 = new Request('GET', 'http://foo.com/baz?bar=bam');
        self::assertSame('/baz?bar=bam', $r1->getRequestTarget());
    }

    public function testBuildsRequestTargetWithFalseyQuery(): void
    {
        $r1 = new Request('GET', 'http://foo.com/baz?0');
        self::assertSame('/baz?0', $r1->getRequestTarget());
    }

    public function testHeaderValueWithWhitespace(): void
    {
        $r = new Request(method: 'GET', uri: 'https://example.com/', headers: [
            'User-Agent' => 'Linux f0f489981e90 5.10.104-linuxkit 1 SMP Wed Mar 9 19:05:23 UTC 2022 x86_64'
        ]);
        self::assertSame([
            'Host' => ['example.com'],
            'User-Agent' => ['Linux f0f489981e90 5.10.104-linuxkit 1 SMP Wed Mar 9 19:05:23 UTC 2022 x86_64']
        ], $r->getHeaders());
    }

    public function testCanGetHeaderAsCsv(): void
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam', headers: [
            'Cookie' => ['a', 'b', 'c']
        ]);
        self::assertSame('a, b, c', $r->getHeaderLine('Cookie'));
        self::assertSame('', $r->getHeaderLine('Bar'));
    }

    public function testSupportNumericHeaders()
    {
        $r = new Request('GET', '', headers: [
            'Content-Length' => 200,
        ]);
        $this->assertSame(['Content-Length' => ['200'], 'Host' => ['']], $r->getHeaders());
        $this->assertSame('200', $r->getHeaderLine('Content-Length'));
    }

    /**
     * @dataProvider provideHeadersContainingNotAllowedChars
     */
    public function testContainsNotAllowedCharsOnHeaderField($header): void
    {
        $this->expectExceptionMessage(
            sprintf(
                'Invalid header name: %s',
                $header
            )
        );
        $r = new Request(
            'GET',
            'http://foo.com/baz?bar=bam',
            headers: [
                $header => 'value'
            ]
        );
    }

    public function provideHeadersContainingNotAllowedChars(): iterable
    {
        return [[' key '], ['key '], [' key'], ['key/'], ['key('], ['key\\'], [' ']];
    }

    public function testWithUriSetsHostIfNotSet(): void
    {
        $r = (new Request('GET', 'http://foo.com/baz?bar=bam'))->withoutHeader('Host');
        self::assertSame([], $r->getHeaders());
        $r2 = $r->withUri(new Uri('http://www.baz.com/bar'), true);
        self::assertSame('www.baz.com', $r2->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri(): void
    {
        $r = new Request('GET', 'http://foo.com/baz?bar=bam');
        self::assertSame(['Host' => ['foo.com']], $r->getHeaders());
        $r2 = $r->withUri(new Uri('http://www.baz.com/bar'));
        self::assertSame('www.baz.com', $r2->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders(): void
    {
        $r = new Request(method: 'GET', uri: '', headers: [
            'ACCEPT' => 'zoobar',
            'accept' => ['foobar', 'zoobar']
        ]);
        self::assertSame(['Accept' => ['zoobar', 'foobar', 'zoobar'], 'Host' => ['']], $r->getHeaders());
        self::assertSame('zoobar, foobar, zoobar', $r->getHeaderLine('Accept'));
    }

    public function testAddsPortToHeader(): void
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        self::assertSame('foo.com:8124', $r->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort(): void
    {
        $r = new Request('GET', 'http://foo.com:8124/bar');
        $r = $r->withUri(new Uri('http://foo.com:8125/bar'));
        self::assertSame('foo.com:8125', $r->getHeaderLine('host'));
    }

    /**
     * @dataProvider provideHeaderValuesContainingNotAllowedChars
     */
    public function testContainsNotAllowedCharsOnHeaderValue(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid header value: %s', $value));

        $r = new Request(
            'GET',
            'http://foo.com/baz?bar=bam',
            headers: [
                'Cookie' => $value
            ]
        );
    }

    public function provideHeaderValuesContainingNotAllowedChars(): iterable
    {
        // Explicit tests for newlines as the most common exploit vector.
        $tests = [
            ["new\nline"],
            ["new\r\nline"],
            ["new\rline"],
            ["new\r\n line"],
        ];

        for ($i = 0; $i <= 0xff; $i++) {
            if (\chr($i) == "\t") { continue;  }
            if (\chr($i) == " ") { continue;  }
            if ($i >= 0x21 && $i <= 0x7e) { continue; }
            if ($i >= 0x80) { continue; }
            $tests[] = ["foo".\chr($i)."bar"];
        }

        return $tests;
    }

    public function testUpdateHostFromUri()
    {
        $request = new Request('GET', '/');
        $request = $request->withUri(new Uri('https://nyholm.tech'));
        $this->assertEquals('nyholm.tech', $request->getHeaderLine('Host'));

        $request = new Request('GET', 'https://example.com/');
        $this->assertEquals('example.com', $request->getHeaderLine('Host'));
        $request = $request->withUri(new Uri('https://nyholm.tech'));
        $this->assertEquals('nyholm.tech', $request->getHeaderLine('Host'));

        $request = new Request('GET', '/');
        $request = $request->withUri(new Uri('https://nyholm.tech:8080'));
        $this->assertEquals('nyholm.tech:8080', $request->getHeaderLine('Host'));

        $request = new Request('GET', '/');
        $request = $request->withUri(new Uri('https://nyholm.tech:443'));
        $this->assertEquals('nyholm.tech', $request->getHeaderLine('Host'));
    }
}
