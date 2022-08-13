<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Dominicus75\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @covers Dominicus75\Psr7\Stream
 * Based on Tobias Nyholm's work and Guzzle
 * @see https://github.com/Nyholm/psr7/blob/master/tests/StreamTest.php
 * @see https://github.com/guzzle/psr7/blob/master/tests/StreamTest.php
 */
class StreamTest extends TestCase
{
    public function testConstructorThrowsExceptionOnInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stream(true);
        new Stream(123);
        new Stream([123, 'abc']);
    }

    public function testConstructorInitializesProperties()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertIsArray($stream->getMetadata());
        $this->assertEquals(4, $stream->getSize());
        $this->assertFalse($stream->eof());
        $stream->close();
    }

    public function testStreamClosesHandleOnDestruct()
    {
        $handle = fopen('php://temp', 'r');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        unset($stream);
        $this->assertFalse(is_resource($handle));
    }

    public function testConvertsToString()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals('data', $stream->__toString());
        $this->assertEquals('data', (string) $stream);
        $stream->close();
    }

    public function testBuildFromString()
    {
        $stream = new Stream('Árvíztűrő tükörfúrógép');
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals('', $stream->getContents());
        $this->assertEquals('Árvíztűrő tükörfúrógép', $stream->__toString());
        $this->assertEquals('Árvíztűrő tükörfúrógép', (string) $stream);
        $stream->close();
    }

    public function testGetsContents()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'Árvíztűrő tükörfúrógép');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals('', $stream->getContents());
        $stream->seek(0);
        $this->assertEquals('Árvíztűrő tükörfúrógép', $stream->getContents());
        $this->assertEquals('', $stream->getContents());
    }

    public function testChecksEof()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertFalse($stream->eof());
        $stream->read(4);
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testGetSize()
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals($size, $stream->getSize());
        $this->assertEquals($size, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent()
    {
        $h = fopen('php://temp', 'w+');
        $this->assertEquals(3, fwrite($h, 'foo'));
        $stream = new Stream($h);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals(3, $stream->getSize());
        $this->assertEquals(4, $stream->write('test'));
        $this->assertEquals(7, $stream->getSize());
        $this->assertEquals(7, $stream->getSize());
        $stream->close();
    }

    public function testProvidesStreamPosition()
    {
        $handle = fopen('php://temp', 'w+');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals(0, $stream->tell());
        $stream->write('foo');
        $this->assertEquals(3, $stream->tell());
        $stream->seek(1);
        $this->assertEquals(1, $stream->tell());
        $this->assertSame(ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testCanDetachStream()
    {
        $r = fopen('php://temp', 'w+');
        $stream = new Stream($r);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $stream->write('foo');
        $this->assertTrue($stream->isReadable());
        $this->assertSame($r, $stream->detach());
        $stream->detach();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());

        $throws = function (callable $fn) use ($stream) {
            try {
                $fn($stream);
                $this->fail();
            } catch (\Exception $e) {
                // Suppress the exception
            }
        };

        $throws(function ($stream) {
            $stream->read(10);
        });
        $throws(function ($stream) {
            $stream->write('bar');
        });
        $throws(function ($stream) {
            $stream->seek(10);
        });
        $throws(function ($stream) {
            $stream->tell();
        });
        $throws(function ($stream) {
            $stream->eof();
        });
        $throws(function ($stream) {
            $stream->getSize();
        });
        $throws(function ($stream) {
            $stream->getContents();
        });
        if (\PHP_VERSION_ID >= 70400) {
            $throws(function ($stream) {
                (string) $stream;
            });
        } else {
            $this->assertSame('', (string) $stream);

            $throws(function ($stream) {
                (string) $stream;
            });
            restore_error_handler();
            restore_exception_handler();
        }

        $stream->close();
    }

    public function testCloseClearProperties()
    {
        $handle = fopen('php://temp', 'r+');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $stream->close();

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->getSize());
        $this->assertEmpty($stream->getMetadata());
    }

}