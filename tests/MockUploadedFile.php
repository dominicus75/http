<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Psr\Http\Message\{UploadedFileInterface, StreamInterface};
use Dominicus75\Psr7\{Stream, UploadedFile};

class MockUploadedFile extends UploadedFile
{

    public function __construct(
        StreamInterface|string $file,
        int $error,
        ?int $size    = null,
        ?string $name = null
    ) {
        if ($file instanceof StreamInterface) {
            $this->stream  = $file;
            $this->tmpName = $this->stream->getMetadata('uri');
        } elseif (\is_string($file)) {
            $this->tmpName = $file;
            try {
                $this->stream = new Stream($this->tmpName);           
            } catch (\RuntimeException $e) { throw $e; }
        }
          
        $realFileSize          = $this->stream->getSize();
        $this->size            = ($realFileSize != $size) || \is_null($size) ? $realFileSize : $size;
        $name                  = \is_null($name) ? $this->stream->getMetadata('uri') : $name;
        $this->clientFilename  = \preg_replace("/[^\w\.\-\/]/i", "", $name);
        $this->clientMediaType = \mime_content_type($this->tmpName);    
    }

    public function moveTo($targetPath): void
    {
        if ($this->moved) { 
            throw new \RuntimeException("Uploaded file has already been moved to a new location"); 
        }

        if ($this->stream->getUsedWrapper($targetPath) === 'file') {
            $target_dir = \dirname($targetPath);
            switch (true) {
                case (\file_exists($targetPath)):
                    throw new \RuntimeException('Target file is already exists'); 
                case (!\file_exists($target_dir)):
                    throw new \RuntimeException('Target directory does not exists.');
                case (\file_exists($target_dir) && !\is_dir($target_dir)):
                    throw new \RuntimeException('Target directory is not a directory.');
                case (\file_exists($target_dir) && !\is_writable($target_dir)):
                    throw new \RuntimeException('Target directory is not writable.');
            }
            $this->moved = (\PHP_SAPI === 'cli'
                ? \rename($this->tmpName, $targetPath)
                : \move_uploaded_file($this->tmpName, $targetPath)
            );                
        } else {
            try {
                $targetStream = new Stream($targetPath, 'c+t', $this->stream);
                $this->moved  = ($targetStream->getSize() === $this->stream->getSize());
            } catch (\RuntimeException $e) { throw $e; }
        }

        if (!$this->moved) { 
            throw new \RuntimeException('Uploaded file could not be moved to '.$targetPath); 
        } else {
            $this->stream->close();
            if (\file_exists($this->tmpName)) { \unlink($this->tmpName); }
        }
    }

}