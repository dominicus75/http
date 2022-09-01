<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface};

/**
 * Value object representing a file uploaded through an HTTP request.
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * @staticvar array The error code can be found in the error segment 
     * of the file array that is created during the file upload by PHP.
     * The error might be found in $_FILES['userfile']['error']. 
     */
    private const ERRORS = [
        \UPLOAD_ERR_OK         => 'There is no error, the file uploaded with success',
        \UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        \UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        \UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        \UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        \UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        \UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
    ];

    /**
     * @var string The temporary filename of the file in which the uploaded file 
     * was stored on the server ($_FILES['userfile']['tmp_name']).
     */
    private string $tmpName;

    /**
     * @var StreamInterface Stream representation of the uploaded file
     */
    private StreamInterface $stream;

    /** 
     * @var int|null The file size in bytes or null if unknown
     * ($_FILES['userfile']['size']).
     */
    private ?int $size = null;

    /** 
     * @var int One of PHP's UPLOAD_ERR_XXX constants
     * ($_FILES['userfile']['error']).
     */
    private int $error = \UPLOAD_ERR_OK;

    /** 
     * @var string|null the original filename sent by the client
     * ($_FILES['userfile']['name']).
     */
    private ?string $clientFilename;

    /** 
     * @var string|null the media type sent by the client
     * ($_FILES['userfile']['type']). For example: "image/gif"
     */
    private ?string $clientMediaType;

    /** 
     * @var bool True, if the uploaded file was moved to a new location
     */
    private bool $moved = false;

    /**
     * The constructor method. Creates a new UploadedFile instance.
     *
     * @param StreamInterface|string $tmpNameOrStream The temporary filename of the file in which 
     * the uploaded file was stored on the server, or a stream which representing the uploaded file
     * @param integer $error The error code associated with this file upload
     * @param integer|null $size The size, in bytes, of the uploaded file
     * @param string|null $name The original name of the file to be uploaded
     * @throws \ValueError $error is int, but not an UPLOAD_ERR_* constant 
     * @throws \InvalidArgumentException for invalid error status
     * @throws \RuntimeException if file uploading fails or file is not a valid uploaded file
     */
    public function __construct(
        StreamInterface|string $tmpNameOrStream,
        int $error,
        ?int $size    = null,
        ?string $name = null
    ) {
        if ($error != \UPLOAD_ERR_OK) {
            if (!isset(self::ERRORS[$error])) {
                throw new \ValueError(
                    'Error status for UploadedFile must be an UPLOAD_ERR_* constant'
                );
            }   
            throw new \RuntimeException(self::ERRORS[$error]);
        } 

        if ($tmpNameOrStream instanceof StreamInterface) {
            $this->stream  = $tmpNameOrStream;
            $this->tmpName = $this->stream->getMetadata('uri');
        } elseif (\is_string($tmpNameOrStream)) {
            if (!\file_exists($tmpNameOrStream)) { 
                throw new \RuntimeException('Uploaded file does not exists'); 
            }
            if (!\is_uploaded_file($tmpNameOrStream)) {
                throw new \RuntimeException('It is not a valid uploaded file');
            }
            $this->tmpName = $tmpNameOrStream;
            $stream = \fopen($this->tmpName, 'r+');
            if (!\is_resource($stream)) {
                throw new \RuntimeException('Unable to open the stream');
            }
            $this->stream = new Stream($stream);           
        }
           
        $realFileSize          = $this->stream->getSize();
        $this->size            = ($realFileSize != $size) || \is_null($size) ? $realFileSize : $size;
        $this->clientFilename  = \preg_replace("/[^\w\.\-]/i", "", $name);
        $this->clientMediaType = \mime_content_type($this->tmpName);    
    }

    ##########################
    # PSR-7 Public interface #
    ##########################

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     * created.
     */
    public function getStream(): StreamInterface
    {
        if ($this->moved) { 
            throw new \RuntimeException("Uploaded file has already been moved to a new location"); 
        } 
        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     * the second or subsequent call to the method.
     */
    public function moveTo($targetPath): void
    {
        if ($this->moved) { 
            throw new \RuntimeException("Uploaded file has already been moved to a new location"); 
        }

        $target_dir = \dirname($targetPath);

        if (\is_writable($target_dir)) {

            if (\file_exists($targetPath)) {
                throw new \RuntimeException($targetPath.' file is already exists'); 
            }

            $this->moved = (\PHP_SAPI === 'cli')
                ? \rename($this->tmpName, $targetPath)
                : \move_uploaded_file($this->tmpName, $targetPath);
                
        } else {
            throw new \RuntimeException($target_dir.' is not writable'); 
        }

        if (!$this->moved) { 
            throw new \RuntimeException('Uploaded file could not be moved to '.$targetPath); 
        } else {
            $this->stream->close();
            \unlink($this->tmpName);
        }
    }
    
    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int { return $this->size; }
    
    /**
     * Retrieve the error associated with the uploaded file.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants. If the file was 
     * uploaded successfully, this method return UPLOAD_ERR_OK.
     */
    public function getError(): int { return $this->error; }
    
    /**
     * Retrieve the filename sent by the client. Do not trust the value 
     * returned by this method. A client could send a malicious filename 
     * with the intention to corrupt or hack your application.
     *
     * @return string|null The filename sent by the client or null if none
     * was provided.
     */
    public function getClientFilename(): ?string { return $this->clientFilename; }
    
    /**
     * Retrieve the media type sent by the client. Do not trust the value 
     * returned by this method. A client could send a malicious media type 
     * with the intention to corrupt or hack your application.
     *
     * @return string|null The media type sent by the client or null if none
     * was provided.
     */
    public function getClientMediaType(): ?string { return $this->clientMediaType; }

}