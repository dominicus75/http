<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface};
use Dominicus75\Psr7\{Stream, UploadedFile};
use PHPUnit\Framework\TestCase;

/**
 * @covers Dominicus75\Psr7\Stream
 * Based on Tobias Nyholm's work and Guzzle
 * @see https://github.com/Nyholm/psr7/blob/master/tests/StreamTest.php
 * @see https://github.com/guzzle/psr7/blob/master/tests/StreamTest.php
 */
class UploadedFileTest extends TestCase
{
    private static string $tmp_dir;
    private static string $upl_dir;
    private static string $tst_dir;
    private static array $throws;
    private static array $errors;
    private static $resource;
    private static Stream $stream;
    private static array $invalid_constructor_args;
    private static array $invalid_error_codes;
    private static array $constructor_exceptions;
    private static array $invalid_target_paths;
    private static array $moveTo_exceptions;

    public static function setUpBeforeClass(): void
    {
        self::$tmp_dir = \sys_get_temp_dir().DIRECTORY_SEPARATOR;
        if (!\is_dir(self::$tmp_dir.DIRECTORY_SEPARATOR.'upload')) { 
            \mkdir(self::$tmp_dir.DIRECTORY_SEPARATOR.'upload'); 
        }
        self::$upl_dir = self::$tmp_dir.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR;
        self::$tst_dir = __DIR__.DIRECTORY_SEPARATOR;
        self::$throws  = [
            'type'    => \TypeError::class,
            'value'   => \ValueError::class,
            'runtime' => \RuntimeException::class,
            'invalid' => \InvalidArgumentException::class
        ];

        self::$errors = [
            \UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            \UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            \UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
            \UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            \UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            \UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];

        $string   = 'John Doe';
        $int      = 33;
        $float    = 88.5;
        $bool     = true;
        $null     = null;
        $array    = [
            'name'   => $string,
            'male'   => $bool,
            'email'  => $null,
            'age'    => $int,
            'weight' => $float
        ];
        $object   = (object) $array;
        self::$resource = \fopen('php://temp', 'rw+');
        self::$stream   = new Stream(self::$resource);

        self::$invalid_constructor_args = [
            'tmpNameOrStream' => [
                'values' => [
                    $null,
                    $bool,
                    $int,
                    $float,
                    $array,
                    self::$resource,
                    $object
                ],
                'throws' => self::$throws['type']
            ],
            'error' => [
                'values' => [
                    $null,
                    $bool,
                    $string,
                    $float,
                    $array,
                    self::$resource,
                    $object
                ],
                'throws' => self::$throws['type']
            ],
            'size' => [
                'values' => [
                    $string,
                    $bool,
                    self::$stream,
                    $float,
                    $array,
                    self::$resource,
                    $object
                ],
                'throws' => self::$throws['type']
            ],
            'name' => [
                'values' => [
                    self::$stream,
                    $bool,
                    $int,
                    $float,
                    $array,
                    self::$resource,
                    $object
                ],
                'throws' => self::$throws['type']
            ]
        ];

        self::$invalid_error_codes    = [
            'values'  => [-10, 5, -1, 9, 10, 42, 123],
            'throws'  => self::$throws['value'],
            'message' => 'Error status for UploadedFile must be an UPLOAD_ERR_* constant'          
        ];

        self::$constructor_exceptions = [
            'file_does_not_exists' => [
                'values'  => [
                    self::$tst_dir.'uploaded_file.txt',
                    self::$tmp_dir.'uploaded_file.txt',
                    self::$upl_dir.'uploaded_file.txt',
                ],
                'throws'  => self::$throws['runtime'],
                'message' => 'Uploaded file does not exists'
            ],
            'file_is_not_uploaded' => [
                'values'  => \glob(self::$tst_dir.'*.*'),
                'throws'  => self::$throws['runtime'],
                'message' => 'It is not a valid uploaded file'
            ],
            'stream_is_unopenable' => [
                'values'  => [

                ],
                'throws'  => self::$throws['runtime'],
                'message' => 'Unable to open the stream'
            ],

        ];

       /* $fh = fopen(self::$tmp_dir.'numbers.txt', "w");
        fwrite($fh, "12345678");
        fclose($fh);*/
    }

    public function testInvalidTmpNameOrStreamArgThrownsExceptions()
    {
        foreach (self::$invalid_constructor_args['tmpNameOrStream']['values'] as $argument) {
            $this->expectException(self::$invalid_constructor_args['tmpNameOrStream']['throws']);
            new UploadedFile($argument, \UPLOAD_ERR_OK);
        }
    }

    public function testInvalidTypedErrorArgThrownsExceptions()
    {
        foreach (self::$invalid_constructor_args['error']['values'] as $argument) {
            $this->expectException(self::$invalid_constructor_args['error']['throws']);
            new UploadedFile(self::$stream, $argument);
        }
    }

    public function testInvalidErrorArgValueThrownsExceptions()
    {
        foreach (self::$invalid_error_codes['values'] as $argument) {
            $this->expectException(self::$invalid_error_codes['throws']);
            $this->expectExceptionMessage(self::$invalid_error_codes['message']);
            new UploadedFile(self::$stream, $argument);
        }
    }

    public function testUploadErrorThrownsExceptions()
    {
        foreach (self::$errors as $code => $message) {
            $this->expectException(self::$throws['runtime']);
            $this->expectExceptionMessage($message);
            new UploadedFile(self::$stream, $code);
        }
    }

    public function testInvalidSizeArgThrownsExceptions()
    {
        foreach (self::$invalid_constructor_args['size']['values'] as $argument) {
            $this->expectException(self::$invalid_constructor_args['size']['throws']);
            new UploadedFile(self::$stream, \UPLOAD_ERR_OK, $argument);
        }
    }

    public function testInvalidNameArgThrownsExceptions()
    {
        foreach (self::$invalid_constructor_args['name']['values'] as $argument) {
            $this->expectException(self::$invalid_constructor_args['name']['throws']);
            new UploadedFile(self::$stream, \UPLOAD_ERR_OK, self::$stream->getSize(), $argument);
        }
    }

    public static function tearDownAfterClass(): void
    {
    }
}