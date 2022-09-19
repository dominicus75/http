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
    private static string $test_dir = __DIR__.DIRECTORY_SEPARATOR;
    private static string $txt_file = __DIR__.DIRECTORY_SEPARATOR.'lorem.txt';
    private static string $png_file = __DIR__.DIRECTORY_SEPARATOR.'tux.png';
    private static string $csv_file = __DIR__.DIRECTORY_SEPARATOR.'random.csv';
    private static array $files = [
        'txt' => __DIR__.DIRECTORY_SEPARATOR.'lorem.txt',
        'png' => __DIR__.DIRECTORY_SEPARATOR.'tux.png',
        'csv' => __DIR__.DIRECTORY_SEPARATOR.'random.csv'
    ];
    private static array $dirs = [
        __DIR__.DIRECTORY_SEPARATOR.'target',
        __DIR__.DIRECTORY_SEPARATOR.'upload',
        __DIR__.DIRECTORY_SEPARATOR.'directory',
        __DIR__.DIRECTORY_SEPARATOR.'foo'
    ];
    private static string $empty = '';

    protected function setUp(): void
    {
        foreach (self::$files as $path) {
            \chmod($path, 0777);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::$dirs as $dir) {
            if (\file_exists($dir)) { 
                if (\is_dir($dir)) {
                    \rmdir($dir); 
                } else { \unlink($dir); }
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$files as $path) {
            \chmod($path, 0777);
        }
        foreach (self::$dirs as $dir) {
            if (\file_exists($dir)) { 
                if (\is_dir($dir)) {
                    \rmdir($dir); 
                } else { \unlink($dir); }
            }
        }
    }

    public function getInvalidConstructorArgs(string $type): array
    {
        $args = [
            'bad_type'    => [
                'values'  => [
                    'bool'      => true,
                    'int'       => 12345,
                    'float'     => 1.23456,
                    'object'    => new \stdClass(),
                    'array'     => [],
                    'callable'  => function(): void { echo "Hello world!"; }
                ],
                'throws'  => \InvalidArgumentException::class,
                'message' => '$resource must be a string or resource.'   
            ],
            'bad_value'   => [
                'values'  => [
                    'html://stream',
                    'telnet://temp',
                    'file://memory'
                ],
                'throws'  => \RuntimeException::class,
                'message' => 'Given stream wapper is invalid.'
            ],
            'file_not_found'   => [
                'values'  => [
                    '/tmp/something.txt',
                    '/etc/something.csv',
                    '/home/somebody/foo.html'
                ],
                'throws'  => \RuntimeException::class,
                'message' => 'Given file does not exists.'
            ],           
            'file_not_readable'   => [
                'values'  => self::$files,
                'throws'  => \RuntimeException::class,
                'message' => 'Given file is not readable.'
            ],           
            'file_not_writable'   => [
                'values'  => self::$files,
                'throws'  => \RuntimeException::class,
                'message' => 'Given file is not writable.'
            ],
            'target_dir_not_found'   => [
                'values'  => self::$dirs,
                'throws'  => \RuntimeException::class,
                'message' => 'Target directory does not exists.'
            ],
            'target_dir_not_writable'   => [
                'values'  => self::$dirs,
                'throws'  => \RuntimeException::class,
                'message' => 'Target directory is not writable.'
            ]
        ];
        return $args[$type];
    }

    public function getValidConstructorArgs(string $type = ''): array
    {
        $args = [
            'resource'    => [
                'values'  => [
                    \fopen(self::$txt_file, 'r+')
                ],
                'results' => [
                    [
                        '__toString' => \fread(\fopen(self::$txt_file, 'r+'), \filesize(self::$txt_file)),
                        'uri'        => self::$txt_file,
                        'size'       => \filesize(self::$txt_file)    
                    ]
                ]
            ],
            'string'      => [
                'values'  => [
                    'php://temp'
                ],
                'results' => [
                    [
                        '__toString' => self::$empty,
                        'uri'        => 'php://temp',
                        'size'       => 0
                    ]
                ]
            ]
        ];
        return empty($type) ? $args : $args[$type];
    }

    public function testConstructorThrowsExceptionOnInvalidArgument(): void
    {
        $bad_type = $this->getInvalidConstructorArgs('bad_type');
        foreach ($bad_type['values'] as $value) {
            $this->expectException($bad_type['throws']);
            $this->expectExceptionMessage($bad_type['message']);
            new Stream($value);
        }
    }

    
    public function testIfStreamWrapperIsInvalidConstructorThrowsExceptions()
    {
        $bad_value = $this->getInvalidConstructorArgs('bad_value');
        foreach ($bad_value['values'] as $value) {
            $this->expectException($bad_value['throws']);
            $this->expectExceptionMessage($bad_value['message']);
            new Stream($value);
        }
    }

    public function testIfGivenOnlyReadableFileDoesNotExistsConstructorThrowsExceptions()
    {
        $file_not_found = $this->getInvalidConstructorArgs('file_not_found');
        foreach ($file_not_found['values'] as $value) {
            $this->expectException($file_not_found['throws']);
            $this->expectExceptionMessage($file_not_found['message']);
            new Stream($value, 'rb');
        }
    }

    public function testIfGivenFileNotReadableConstructorThrowsExceptions()
    {
        $file_not_readable = $this->getInvalidConstructorArgs('file_not_readable');
        foreach ($file_not_readable['values'] as $value) {
            \chmod($value, 0);
            $this->expectException($file_not_readable['throws']);
            $this->expectExceptionMessage($file_not_readable['message']);
            new Stream($value, 'rb');
        }
    }

    public function testIfGivenFileNotWritableConstructorThrowsExceptions()
    {
        $file_not_writable = $this->getInvalidConstructorArgs('file_not_writable');
        foreach ($file_not_writable['values'] as $value) {
            \chmod($value, 0444);
            $this->expectException($file_not_writable['throws']);
            $this->expectExceptionMessage($file_not_writable['message']);
            new Stream($value);
        }
    }

    public function testIfGivenFileAndTargetDirNotExistsConstructorThrowsExceptions()
    {
        $target_dir_not_found = $this->getInvalidConstructorArgs('target_dir_not_found');
        foreach ($target_dir_not_found['values'] as $value) {
            $this->expectException($target_dir_not_found['throws']);
            $this->expectExceptionMessage($target_dir_not_found['message']);
            new Stream($value.\DIRECTORY_SEPARATOR.'foo.txt');
        }
    }

    public function testIfTargetDirExistsButNotWritableConstructorThrowsExceptions()
    {
        $target_dir_not_writable = $this->getInvalidConstructorArgs('target_dir_not_writable');
        foreach ($target_dir_not_writable['values'] as $value) {
            \mkdir($value, 0444);
            $this->expectException($target_dir_not_writable['throws']);
            $this->expectExceptionMessage($target_dir_not_writable['message']);
            new Stream($value.\DIRECTORY_SEPARATOR.'foo.txt');
        }
    }

    public function testConstructorInitializesProperties()
    {
        $arguments = $this->getValidConstructorArgs();

        foreach ($arguments as $type => $arg) {
            foreach ($arg['values'] as $index => $value) {
                $stream = new Stream($value);
                $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
                $this->assertTrue($stream->isReadable());
                $this->assertTrue($stream->isWritable());
                $this->assertTrue($stream->isSeekable());
                $this->assertEquals($arg['results'][$index]['uri'], $stream->getMetadata('uri'));
                $this->assertIsArray($stream->getMetadata());
                $this->assertEquals($arg['results'][$index]['size'], $stream->getSize());
                $this->assertFalse($stream->eof());
                $stream->close();
            }
        }
    }

    public function testStreamClosesHandleOnDestruct()
    {
        $arguments = $this->getValidConstructorArgs();

        foreach ($arguments as $type => $arg) {
            foreach ($arg['values'] as $index => $value) {
                $stream = new Stream($value);
                $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
                unset($stream);
                $this->assertFalse(is_resource($value));   
            }
        }
    }

    public function testConvertsToString()
    {
        $arguments = $this->getValidConstructorArgs();

        foreach ($arguments as $type => $arg) {
            foreach ($arg['values'] as $index => $value) {
                $stream = new Stream($value);
                $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->getContents());
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->__toString());
                $this->assertEquals($arg['results'][$index]['__toString'], (string) $stream);
                $this->assertTrue($stream->eof());
                $this->assertEquals(self::$empty, $stream->getContents());
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->__toString());
                $this->assertEquals($arg['results'][$index]['__toString'], (string) $stream);
                $stream->rewind();
                $this->assertFalse($stream->eof());
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->getContents());
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->__toString());
                $this->assertEquals($arg['results'][$index]['__toString'], (string) $stream);
                $stream->close();
            }
        }
    }

    public function testBuildFromString()
    {
        $argument = $this->getValidConstructorArgs('string');

        $stream = new Stream($argument['values'][0]);
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals(self::$empty, $stream->getContents());
        $this->assertEquals($argument['results'][0]['__toString'], $stream->__toString());
        $this->assertEquals($argument['results'][0]['__toString'], (string) $stream);
        $this->assertTrue($stream->eof());
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $this->assertEquals($argument['results'][0]['__toString'], $stream->getContents());
        $this->assertEquals($argument['results'][0]['__toString'], $stream->__toString());
        $this->assertEquals($argument['results'][0]['__toString'], (string) $stream);
        $stream->close();

        $stream = new Stream();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals(self::$empty, $stream->getContents());
        $this->assertEquals(self::$empty, $stream->__toString());
        $this->assertEquals(self::$empty, (string) $stream);
        $this->assertTrue($stream->eof());
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $this->assertEquals(self::$empty, $stream->getContents());
        $this->assertEquals(self::$empty, $stream->__toString());
        $this->assertEquals(self::$empty, (string) $stream);
        $stream->close();
    }

    public function testGetsContents()
    {
        $arguments = $this->getValidConstructorArgs();

        foreach ($arguments as $type => $arg) {
            foreach ($arg['values'] as $index => $value) {
                $stream = new Stream($value);
                $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->getContents());
                $this->assertTrue($stream->eof());
                $stream->seek(0);
                $this->assertFalse($stream->eof());
                $this->assertEquals($arg['results'][$index]['__toString'], $stream->getContents());
                $this->assertEquals(self::$empty, $stream->getContents());
                $stream->close();
            }
        }
    }

    public function testChecksEof()
    {
        $arguments = $this->getValidConstructorArgs();

        foreach ($arguments as $type => $arg) {
            foreach ($arg['values'] as $index => $value) {
                $stream = new Stream($value);
                $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
                $this->assertSame(0, $stream->tell());
                $this->assertFalse($stream->eof(), 'Pointer: '.(string)$stream->tell().', size: '.(string)$stream->getSize());
                $this->assertSame($arg['results'][$index]['__toString'], $stream->getContents());
                $this->assertTrue($stream->eof(), 'Pointer: '.(string)$stream->tell().', size: '.(string)$stream->getSize());
                $stream->close();
            }
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
        $arguments = $this->getValidConstructorArgs();

        foreach ($arguments as $type => $arg) {
            foreach ($arg['values'] as $index => $value) {
                $stream = new Stream($value);
                $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
                $stream->close();
                $this->assertEquals(self::$empty, $stream->__toString());
                $this->assertEquals(self::$empty, (string) $stream);
                $this->assertFalse($stream->isSeekable());
                $this->assertFalse($stream->isReadable());
                $this->assertFalse($stream->isWritable());
                $this->assertNull($stream->getSize());
                $this->assertEmpty($stream->getMetadata()); 
                $this->assertNull($stream->getMetadata('uri'));  
                $this->assertNull($stream->detach());
                $this->assertTrue($stream->eof());
            }
        }
    }

}