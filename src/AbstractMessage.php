<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{MessageInterface, StreamInterface};

/**
 * HTTP messages consist of requests from a client to a server and responses
 * from a server to a client. This interface defines the methods common to
 * each.
 *
 * @link http://www.ietf.org/rfc/rfc7230.txt
 * @link http://www.ietf.org/rfc/rfc7231.txt
 * @link https://www.rfc-editor.org/rfc/rfc9110.html
 */
abstract class AbstractMessage implements MessageInterface
{

    /**
     * The string contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @var string
     */
    protected string $version;

    /**
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * @var array
     */
    protected array $headers = [
        'Cache-Control'     => null,
        'Connection'        => null,
        'Content-Encoding'  => null,
        'Content-Length'    => null,
        'Content-MD5'       => null,
        'Content-Type'      => null,
        'Date'              => null,
        'Pragma'            => null,
        'Trailer'           => null,
        'Transfer-Encoding' => null,
        'TE'                => null,
        'Upgrade'           => null,
        'Via'               => null,
        'Warning'           => null
    ];

    /**
     * Describes a data stream. Typically, an instance will wrap a PHP stream
     *
     * @var StreamInterface
     */
    protected StreamInterface $body;

    ##########################
    # PSR-7 Public interface #
    ##########################

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string { return $this->version; }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     * @return static
     * @throws \InvalidArgumentException for invalid protocol version.
     */
    public function withProtocolVersion($version): AbstractMessage
    {
        if ($version === $this->version) { return $this; }
        try {
            $clone = clone $this;
            $clone->setProtocolVersion($version);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * Retrieves all message header values.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        $result = [];
        foreach ($this->headers as $name => $value) {
            if (!\is_null($value)) { $result[$name] = $value; }
        }
        return $result;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderdName($name);
        return isset($this->headers[$name]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method 
     *    returns an empty array.
     */
    public function getHeader($name): array
    {
        $name = $this->normalizeHeaderdName($name);
        return $this->hasHeader($name) ? $this->headers[$name] : [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method returns an empty string.
     */
    public function getHeaderLine($name): string
    {
        $name   = $this->normalizeHeaderdName($name);
        $result = $this->getHeader($name);
        return !empty($result) ? \implode(',', $result) : '';
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value): AbstractMessage
    {
        try {
            $clone = clone $this;
            $clone->setHeader($name, $value, true);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value): AbstractMessage
    {
        try {
            $clone = clone $this;
            $clone->setHeader($name, $value);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): AbstractMessage
    {
        $clone = clone $this;
        $name  = $this->normalizeHeaderdName($name);
        if ($clone->hasHeader($name)) { $clone->headers[$name] = null; }
        return $clone;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface { return $this->body; }

    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): AbstractMessage
    {
        if ($body === $this->body) { return $this; }
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    #####################################
    # Protected, non-standard functions #
    #####################################

    /**
     * Sets the HTTP protocol version.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @param string $version
     * @return self
     * @throws \InvalidArgumentException When the protocol version is not valid.
     */
    protected function setProtocolVersion(string $version = ''): self
    {
        if (empty($version)) {
            $this->setProtocolVersion(explode('/', $_SERVER['SERVER_PROTOCOL'])[1]);
        } elseif (\preg_match("/^(1\.0|1\.1|2\.0)$/", $version)) {
            $this->version = $version;
        } else {
            throw new \InvalidArgumentException($version.' is not a valid HTTP protocol version');
        }
        return $this;
    }

    /**
     * Returns the normalized header field name. E.g. 'HTTP_HOST' returns as 'Host'.
     *
     * @param string $fieldName
     * @return string normalized name
     */
    protected function normalizeHeaderdName(string $name): string
    {
        $name = \str_replace('HTTP_', '', $name);
        return \ucwords(\str_replace('_', '-', \strtolower($name)), '-');
    }

    /**
     * Sets a header field. The keys represent the header name as it will be sent over the wire, 
     * and each value is an array of strings associated with the header.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @param bool $update Is it update of existing field (true) or create new (false)?
     * @return AbstractMessage
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    protected function setHeader(string $name, string|array $value, bool $update = false): self
    {
        $field = $this->normalizeHeaderdName($name);

        if (!\array_key_exists($field, $this->headers)) { throw new \InvalidArgumentException('Invalid header name: '.$field); }

        if (!\is_array($value)) { $value = explode(',', $value); }

        foreach ($value as $item) { $headerField[] = \preg_replace('/[^\x20-\x7E]/', '', $item); }

        if (!$this->hasHeader($field) || $update) {
            $this->headers[$field] = $headerField;
        } else {
            $this->headers[$field] = \array_merge($this->headers[$field], $headerField);
        }

        return $this;
    }

    /**
     * Sets the headers property.
     *
     * @param array $headers
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    protected function setHeaders(array $headers = []): self
    {
        try {
            if (empty($headers)) {
                foreach ($_SERVER as $name => $value) {
                    if (str_starts_with($name, 'HTTP')) { $this->setHeader($name, $value); }
                }
            } else {
                foreach ($headers as $name => $value) { $this->setHeader($name, $value); }
            }
        } catch (\InvalidArgumentException $e) { throw $e; }

        return $this;
    }

}