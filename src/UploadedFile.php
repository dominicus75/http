<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface};

/**
 * Value object representing a file uploaded through an HTTP request.
 */
class UploadedFile implements UploadedFileInterface
{

    /** 
     * @var StreamInterface|null a stream representing the uploaded file
     */
    private ?StreamInterface $stream = null;

    /**
     * @var string|null The temporary filename of the file in which the uploaded file 
     * was stored on the server ($_FILES['userfile']['tmp_name']).
     */
    private $file = null;

    /** 
     * @var bool True, if the uploaded file was moved to a new location
     */
    private bool $moved = false;

    /** 
     * @var int|null The file size in bytes or null if unknown
     * ($_FILES['userfile']['size']).
     */
    private ?int $size = null;

    /** 
     * @var int One of PHP's UPLOAD_ERR_XXX constants
     * ($_FILES['userfile']['error']).
     */
    private int $error;

    /** 
     * @var string the original filename sent by the client
     * ($_FILES['userfile']['name']).
     */
    private string $clientFilename;

    /** 
     * @var string the media type sent by the client
     * ($_FILES['userfile']['type']). For example: "image/gif"
     */
    private string $clientMediaType;



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
            throw new \RuntimeException("The uploaded file was moved to a new location"); 
        } elseif (isset($this->stream)) { 
            return $this->stream; 
        } else {
            try {
                if (isset($this->file)) {
                    return new Stream($this->file);
                } else {
                    throw new \RuntimeException("The uploaded file cannot be opened");
                }
            } catch (\InvalidArgumentException $e) { throw $e; }
        }
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
    public function moveTo(string $targetPath): void
    {

    }
    
    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int
    {
        return null;
    }
    
    /**
     * Retrieve the error associated with the uploaded file.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants. If the file was 
     * uploaded successfully, this method return UPLOAD_ERR_OK.
     */
    public function getError(): int
    {
        return \UPLOAD_ERR_OK;
    }
    
    /**
     * Retrieve the filename sent by the client. Do not trust the value 
     * returned by this method. A client could send a malicious filename 
     * with the intention to corrupt or hack your application.
     *
     * @return string|null The filename sent by the client or null if none
     * was provided.
     */
    public function getClientFilename(): ?string
    {
        return null;
    }
    
    /**
     * Retrieve the media type sent by the client. Do not trust the value 
     * returned by this method. A client could send a malicious media type 
     * with the intention to corrupt or hack your application.
     *
     * @return string|null The media type sent by the client or null if none
     * was provided.
     */
    public function getClientMediaType(): ?string
    {
        return null;
    }

}
