<?php declare(strict_types=1);

namespace Dominicus75\Http;

use Psr\Http\Message\{MessageInterface, StreamInterface, RequestInterface, UriInterface};

/**
 * Representation of an outgoing, client-side request.
 *
 */
class Request extends AbstractMessage implements RequestInterface
{

    /**
     * The request target, usually a URL, or the absolute path of the protocol, port, 
     * and domain are usually characterized by the request context. The format of this 
     * request target varies between different HTTP methods. It can be
     *  origin-form: An absolute path, ultimately followed by a '?' and query string. 
     *   This is the most common form and is used with GET, POST, HEAD, and OPTIONS methods. 
     *  absolute-form: A complete URL is mostly used with GET when connected to a proxy.
     *  authority-form: the authority component of a URL, consisting of the domain name and 
     *   optionally the port. It is only used with CONNECT when setting up an HTTP tunnel.
     *  asterisk-form: "*" is used with OPTIONS method
     *
     * @var string
     */
    protected string $requestTarget;

    /**
     * The request method
     *
     * @var string
     */
    protected string $method;

    /**
     * UriInterface instance representing the URI of the request
     *
     * @var UriInterface
     */
    protected UriInterface $uri;

	/**
     * The constructor method
     * 
     * @param string $method
     * @return self
     * @throws \InvalidArgumentException if 
     * - $method is not valid HTTP method
     * - a header name is not valid
	 */
	public function __construct(
        string $method = '',
        array $headers = [],
        string|UriInterface|null $uri = null,
        string|StreamInterface|null $body = null
    ) {
        parent::__construct();

        $method = !empty($method) ? $method : $_SERVER['REQUEST_METHOD'];

        if (\preg_match('/^(options|get|head|put|post|delete|patch)$/is', $method)) {
            $this->method = $method;
        } else { throw new \InvalidArgumentException($method.' is not a valid HTTP method.'); }

        $this->headers['A-IM']                           = null;
        $this->headers['Accept']                         = null;
        $this->headers['Accept-Charset']                 = null;
        $this->headers['Accept-Datetime']                = null;
        $this->headers['Accept-Encoding']                = null;
        $this->headers['Accept-Language']                = null;
        $this->headers['Access-Control-Request-Method']  = null;
        $this->headers['Access-Control-Request-Headers'] = null;
        $this->headers['Authorization']                  = null;
        $this->headers['Cookie']                         = null;
        $this->headers['Expect']                         = null;
        $this->headers['Forwarded']                      = null;
        $this->headers['From']                           = null;
        $this->headers['Host']                           = null;
        $this->headers['HTTP2-Settings']                 = null;
        $this->headers['If-Match']                       = null;
        $this->headers['If-Modified-Since']              = null;
        $this->headers['If-None-Match']                  = null;
        $this->headers['If-Range']                       = null;
        $this->headers['If-Unmodified-Since']            = null;
        $this->headers['Max-Forwards']                   = null;
        $this->headers['Origin']                         = null;
        $this->headers['Prefer']                         = null;
        $this->headers['Proxy-Authorization']            = null;
        $this->headers['Range']                          = null;
        $this->headers['Referer']                        = null;
        $this->headers['User-Agent']                     = null;
        $this->headers['Upgrade-Insecure-Requests']      = null;
        $this->headers['X-Forwarded-For']                = null;
        $this->headers['X-Forwarded-Host']               = null;
        $this->headers['X-Forwarded-Proto']              = null;
        $this->headers['X-Requested-With']               = null;
        $this->headers['X-Csrf-Token']                   = null;

        try {
            if (empty($headers)) {
                foreach ($_SERVER as $name => $value) { 
                    if (str_starts_with($name, 'HTTP')) { $this->setHeaderField($name, $value); }
                }
            } else {
                foreach ($headers as $name => $value) { $this->setHeaderField($name, $value); }
            }
        } catch (\InvalidArgumentException $e) { throw $e; }

        if($uri instanceof UriInterface) {
            $this->uri = $uri;
        } else {
            $this->uri = \is_string($uri) ? new Uri($uri) : new Uri();
        }

        $path                 = $this->uri->getPath();
        $query                = $this->uri->getQuery();
        $this->requestTarget  = empty($path) ? '/' : $path;
        $this->requestTarget .= empty($query) ? '' : '?'.$query;

        if($body instanceof StreamInterface || \is_null($body)) {
            $this->body = $body;
        } elseif(\is_string($body)) {
            $this->body = new Stream($body);
        }
	}

    /**
     * Retrieves the message's request target.
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method returns the string "/".
     *
     * @return string
     */
    public function getRequestTarget(): string { return $this->requestTarget; }

    /**
     * Return an instance with the specific request-target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget(mixed $requestTarget): self
    {
        if ($requestTarget === $this->requestTarget) { return $this; }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;  
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string the request method.
     */
    public function getMethod(): string { return $this->method; }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method): self
    {
        if ($method === $this->method) { return $this; }

        if (!\preg_match('/^(options|get|head|put|post|delete|patch)$/is', $method)) {
            throw new \InvalidArgumentException($method.' is not a valid HTTP method.');
        } 

        $clone = clone $this;
        $clone->method = $method;
        return $clone;  
    }

    /**
     * Retrieves the URI instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface { return $this->uri; }

    /**
     * Returns an instance with the provided URI.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if ($uri === $this->uri) { return $this; }

        $clone      = clone $this;
        $clone->uri = $uri;
        $reqHost    = $clone->getHeaderLine('Host');
        $uriHost    = $clone->getUri()->getHost();

        if ((!$preserveHost xor ($preserveHost && empty($reqHost))) && !empty($uriHost)) { 
            $clone->setHeaderField('Host', $uriHost, true); 
        }

        return $clone;  
    }

}