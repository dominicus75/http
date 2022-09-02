<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Psr\Http\Message\StreamInterface;
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
    private array $invalid_constructor_arguments;
    private array $valid_constructor_arguments;
    private array $results_for_valid_arguments;
    private string $textfile;
    private int $filesize;
    private int $textsize;
    private $resource;
    private string $string;
    private string $empty = '';

    protected function setUp(): void
    {
        $this->invalid_constructor_arguments = [
            'bool'      => true,
            'int'       => 12345,
            'float'     => 1.23456,
            'object'    => new \stdClass(),
            'array'     => [],
            'callable'  => function(): void { echo "Hello world!"; }
        ];

        $this->textfile = __DIR__.DIRECTORY_SEPARATOR.'lorem.txt';
        $this->filesize = \filesize($this->textfile);
        $this->resource = fopen($this->textfile, 'r+');
        $this->string   = \fread($this->resource, $this->filesize);
        $this->textsize = \strlen($this->string);

        $this->valid_constructor_arguments = [
            'resource' => $this->resource,
            'string'   => 'php://temp',
            'empty'    => $this->empty
        ];

        $this->results_for_valid_arguments = [
            'resource' => [
                '__toString' => $this->string,
                'uri'        => $this->textfile,
                'size'       => $this->filesize
            ],
            'string' => [
                '__toString' => $this->empty,
                'uri'        => 'php://temp',
                'size'       => 0
            ],
            'empty' => [
                '__toString' => $this->empty,
                'uri'        => 'php://temp',
                'size'       => 0
            ]
        ];
    }

    public function testConstructorThrowsExceptionOnInvalidArgument(): void
    {
        foreach ($this->invalid_constructor_arguments as $type => $value) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('$resource must be a string or resource.');
            new Stream($value);
        }
    }

    public function testConstructorInitializesProperties()
    {
        foreach ($this->valid_constructor_arguments as $type => $value) {
            $stream = new Stream($value);
            $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
            $this->assertTrue($stream->isReadable());
            $this->assertTrue($stream->isWritable());
            $this->assertTrue($stream->isSeekable());
            $this->assertEquals($this->results_for_valid_arguments[$type]['uri'], $stream->getMetadata('uri'));
            $this->assertIsArray($stream->getMetadata());
            $this->assertEquals($this->results_for_valid_arguments[$type]['size'], $stream->getSize());
            $this->assertFalse($stream->eof());
            $stream->close();
        }
    }

    public function testStreamClosesHandleOnDestruct()
    {
        foreach ($this->valid_constructor_arguments as $type => $value) {
            $stream = new Stream($value);
            $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
            unset($stream);
            $this->assertFalse(is_resource($value));   
        }
    }

    public function testConvertsToString()
    {
        foreach ($this->valid_constructor_arguments as $type => $value) {
            $stream = new Stream($value);
            $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
            $this->assertEquals($this->empty, $stream->getContents());
            $this->assertEquals($this->results_for_valid_arguments[$type]['__toString'], $stream->__toString());
            $this->assertEquals($this->results_for_valid_arguments[$type]['__toString'], (string) $stream);
            $this->assertTrue($stream->eof());
            $stream->rewind();
            $this->assertFalse($stream->eof());
            $this->assertEquals($this->results_for_valid_arguments[$type]['__toString'], $stream->getContents());
            $this->assertEquals($this->results_for_valid_arguments[$type]['__toString'], $stream->__toString());
            $this->assertEquals($this->results_for_valid_arguments[$type]['__toString'], (string) $stream);
            $stream->close();
        }
    }

    public function testBuildFromString()
    {
        $stream = new Stream($this->valid_constructor_arguments['string']);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals($this->empty, $stream->getContents());
        $this->assertEquals($this->results_for_valid_arguments['string']['__toString'], $stream->__toString());
        $this->assertEquals($this->results_for_valid_arguments['string']['__toString'], (string) $stream);
        $this->assertTrue($stream->eof());
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $this->assertEquals($this->results_for_valid_arguments['string']['__toString'], $stream->getContents());
        $this->assertEquals($this->results_for_valid_arguments['string']['__toString'], $stream->__toString());
        $this->assertEquals($this->results_for_valid_arguments['string']['__toString'], (string) $stream);
        $stream->close();

        $stream = new Stream($this->valid_constructor_arguments['empty']);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals($this->empty, $stream->getContents());
        $this->assertEquals($this->results_for_valid_arguments['empty']['__toString'], $stream->__toString());
        $this->assertEquals($this->results_for_valid_arguments['empty']['__toString'], (string) $stream);
        $this->assertTrue($stream->eof());
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $this->assertEquals($this->results_for_valid_arguments['empty']['__toString'], $stream->getContents());
        $this->assertEquals($this->results_for_valid_arguments['empty']['__toString'], $stream->__toString());
        $this->assertEquals($this->results_for_valid_arguments['empty']['__toString'], (string) $stream);
        $stream->close();
    }

    public function testGetsContents()
    {
        foreach ($this->valid_constructor_arguments as $type => $value) {
            $stream = new Stream($value);
            $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
            $this->assertEquals($this->empty, $stream->getContents());
            $this->assertTrue($stream->eof());
            $stream->seek(0);
            $this->assertFalse($stream->eof());
            $this->assertEquals($this->results_for_valid_arguments[$type]['__toString'], $stream->getContents());
            $this->assertEquals($this->empty, $stream->getContents());
            $stream->close();
        }
    }

    public function testChecksEof()
    {
        foreach ($this->valid_constructor_arguments as $type => $value) {
            $stream = new Stream($value);
            $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
            $this->assertSame($this->results_for_valid_arguments[$type]['size'], $stream->tell());
            $this->assertFalse($stream->eof(), 'Pointer: '.(string)$stream->tell().', size: '.(string)$stream->getSize());
            $this->assertSame($this->empty, $stream->read(1));
            $this->assertTrue($stream->eof(), 'Pointer: '.(string)$stream->tell().', size: '.(string)$stream->getSize());
            $stream->close();
        }
    }

    public function testGetSize()
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = new Stream($handle);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
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
        $stream->close();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());

        $throws = function (callable $fn) use ($stream) {
            try {
                $fn($stream);
            } catch (\Exception $e) {
                $this->assertStringContainsString('Stream is detached', $e->getMessage());
                return;
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
    }

    public function testCloseClearProperties()
    {
        foreach ($this->valid_constructor_arguments as $type => $value) {
            $stream = new Stream($value);
            $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
            $stream->close();
            $this->assertEquals($this->empty, $stream->__toString());
            $this->assertEquals($this->empty, (string) $stream);
            $this->assertFalse($stream->isSeekable());
            $this->assertFalse($stream->isReadable());
            $this->assertFalse($stream->isWritable());
            $this->assertNull($stream->getSize());
            $this->assertEmpty($stream->getMetadata()); 
            $this->assertNull($stream->getMetadata('foo'));  
            $this->assertNull($stream->detach());
            $this->assertTrue($stream->eof());
        }
    }

}