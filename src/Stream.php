<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
class Stream implements StreamInterface
{
    /**
     * @var string List of registered streams available on the running system as regex pattern
     */
    private string $wrappers;

    /** @var resource|null  */
    protected $stream = null;

    /**
     * Creates a new PSR-7 stream.
     *
     * @param string|resource $resource
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($resource = '')
    {
        $this->wrappers = '('.implode('|', stream_get_wrappers()).')';

        if (\is_resource($resource)) {
            $this->stream = $resource;
        } elseif (\is_string($resource)) {
            if (empty($resource)) { 
                $this->stream = \fopen('php://temp', 'rw+'); 
            } elseif ($this->hasWrapper($resource) xor \file_exists($resource)) {
                $this->stream = \fopen($resource, 'rw+');
            } else {
                throw new \RuntimeException('Unable to open the stream. Given resource does not exists.');
            }
        } else {
            throw new \InvalidArgumentException('$resource must be a string or resource.');
        }
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct() { $this->close(); }


    ##########################
    # PSR-7 Public interface #
    ##########################

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString(): string
    {
        if (!isset($this->stream) || !$this->isReadable()) { return ''; }
        if ($this->isSeekable()) {
            try {
                $this->rewind();
            } catch (\RuntimeException $e) { return ''; }
        }
        return $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            \fclose($this->stream);
            $this->detach();
        }
    }

    /**
     * Separates any underlying resources from the stream.
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        if (!isset($this->stream)) { return null; }
        $result = $this->stream;
        unset($this->stream);
        return $result;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): int|null
    {
        if (!isset($this->stream)) { return null; }
        $stat = \fstat($this->stream);
        return $stat['size'] ?? null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell(): int
    {
        if (!isset($this->stream)) { throw new \RuntimeException('Stream is detached'); }
        $result = \ftell($this->stream);
        if ($result === false) {
            throw new \RuntimeException('Could not get the position of the pointer in stream.');
        }
        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return (!isset($this->stream) || \feof($this->stream));
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->getMetadata('seekable') ?? false;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.
     *     SEEK_SET: Set position equal to offset bytes
     *     SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $whence = (int) $whence;
        if (!isset($this->stream)) { throw new \RuntimeException('Stream is detached'); }
        if (!$this->isSeekable())  { throw new \RuntimeException('Stream is not seekable'); }
        if (\fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position '.$offset);
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * @see seek()
     * @link https://www.php.net/manual/en/function.rewind.php
     * @throws \RuntimeException on failure or ff the stream is not seekable.
     */
    public function rewind(): void
    {
        if (!isset($this->stream))   { throw new \RuntimeException('Stream is detached'); }
        if (!$this->isSeekable())    { throw new \RuntimeException('Stream is not seekable'); }
        if (!\rewind($this->stream)) { throw new \RuntimeException('Could not rewind stream.'); }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        if (isset($this->stream)) {
            $mode = $this->getMetadata('mode');
            return $mode ? (bool) \preg_match('/a|w|r\+|rb\+|rw|x|c/', $mode) : false;
        } else { return false; }
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string): int
    {
        if (!isset($this->stream)) { throw new \RuntimeException('Stream is detached'); }
        if (!$this->isWritable()) { throw new \RuntimeException('Stream is not writable'); }
        $result = \fwrite($this->stream, $string);
        if ($result === false) { throw new \RuntimeException('Unable to write to stream'); }
        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        if (isset($this->stream)) {
            $mode = $this->getMetadata('mode');
            return $mode ? (bool) \preg_match('/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/', $mode) : false;
        } else { return false; }
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     * @throws \TypeError if the provided type is not allowed.
     */
    public function read($length): string
    {
        if (!is_int($length))      { throw new \TypeError('Length parameter must be an integer'); }
        if (!isset($this->stream)) { throw new \RuntimeException('Stream is detached'); }
        if (!$this->isReadable())  { throw new \RuntimeException('Stream is not readable'); }
        if (0 === $length)         { return ''; }
        if ($length < 0)           { throw new \ValueError('Argument #1 ($length) must be greater than 0'); }
        $result = \fread($this->stream, $length);
        if (false === $result)     { throw new \RuntimeException('Unable to read from stream'); }
        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while reading.
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) { throw new \RuntimeException('Stream is detached'); }
        if (!$this->isReadable()) { throw new \RuntimeException('Stream is not readable'); }
        $result = \stream_get_contents($this->stream);
        if (false === $result) { throw new \RuntimeException('Unable to read from stream'); }
        return $result;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null): mixed
    {
        if (!isset($this->stream)) { return $key ? null : []; }
        $meta = \stream_get_meta_data($this->stream);
        return \is_null($key) ? $meta : ($meta[$key] ?? null);
    }

    ##########################
    # non-standard functions #
    ##########################

	/**
	 * Retrieve list of registered streams available on the running system
	 * @return string list of registered streams as regex pattern
	 */
	public function getWrappers(): string { return $this->wrappers; }

    /**
     * Checks if the given string has a stream wrapper or not 
     *
     * @param string $path
     * @return boolean true, if path has a wrapper, false otherwise
     */
    public function hasWrapper(string $path): bool
    {
        return (bool) \preg_match('/^'.$this->wrappers.'\:\/\//i', $path);
    }

}