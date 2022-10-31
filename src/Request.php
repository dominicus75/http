<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};

/**
 * Representation of an incoming HTTP request.
 *
 */
class Request extends AbstractMessage implements RequestInterface
{
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
     * The request target, usually a URL, or the absolute path of the protocol, port,
     * and domain are usually characterized by the request context. The format of this
     * request target varies between different HTTP methods. 
     *
     * @var string
     */
    protected string $requestTarget;

    /**
     * The constructor method. Creates a new Request instance.
     *
     * @param string $method the HTTP method name
     * @param string|UriInterface|null|null $uri the requested URI
     * @param string $version the HTTP protocol version as string
     * @param array $headers the HTTP headers
     * @param string|StreamInterface $body the request body
     * @return self
     * @throws \InvalidArgumentException if
     *  - if $method is not a valid HTTP method,
     *  - if $uri string is invalid
     *  - if HTTP protocol $version is not valid
     *  - if a header name is not valid
     *  - if writing of body to stream fails
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        string $version = '1.1',
        array  $headers = [],
        string|StreamInterface $body = ''
    ) {
        try {
            $this->setMethod($method);

            if ($uri instanceof UriInterface) {
                $this->uri = $uri;
            } else {
                try {
                    $this->uri = new Uri($uri);
                } catch (\InvalidArgumentException $e) { throw $e; }
            }

            $this->setProtocolVersion($version);
    
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

            $this->setHostHeader();
            $this->setHeaders($headers);
            
            if($body instanceof StreamInterface) {
                $this->body = $body;
            } elseif(\is_string($body)) {
                try {
                    $this->body = new Stream(content: $body);
                } catch (\RuntimeException $e) {
                    throw new \InvalidArgumentException($e->getMessage());
                }
            }

            $this->setRequestTarget();
        } catch (\InvalidArgumentException $e) { throw $e; }
    }

    ##########################
    # PSR-7 Public interface #
    ##########################

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
     * request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     * @throws \InvalidArgumentException if given request target is invalid
     */
    public function withRequestTarget(mixed $requestTarget): self
    {
        if ($requestTarget === $this->requestTarget) { return $this; }
        if (\preg_match('/([^'.Uri::BASECHAR.Uri::GENDELIM.']+)/i', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target'.Uri::$encoder['wholeuri']);
        }
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
        try {
            $clone = clone $this;
            $clone->setMethod($method);
            return $clone;
        } catch (\InvalidArgumentException $e) { throw $e; }
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

        if ((!$preserveHost xor ($preserveHost && empty($clone->getHeaderLine('Host')))) && !empty($clone->getUri()->getHost())) {
            $clone->setHostHeader(update: true);
        }

        return $clone;
    }

    #####################################
    # Protected, non-standard functions #
    #####################################

    /**
     * Sets the request method property.
     *
     * @param string $method the name of the HTTP request method
     * @return self
     * @throws \InvalidArgumentException when method name is invalid
     */
    private function setMethod(string $method = ''): self
    {
        $method = $method != '' ? $method : $_SERVER['REQUEST_METHOD'];

        if (\preg_match('/^(options|get|head|put|post|delete|patch)$/is', $method)) {
            $this->method = strtoupper($method);
            return $this;
        } else { throw new \InvalidArgumentException($method.' is not a valid HTTP method.'); }
    }

	/**
	 * Sets the requestTarget property from Uri instance.
	 * 
	 * @return Request
	 */
	private function setRequestTarget(): self 
    {
        $path                 = $this->uri->getPath();
        $query                = $this->uri->getQuery();
        $this->requestTarget  = $path  === '' ? '/' : $path;
        $this->requestTarget .= $query === '' ? '' : '?'.$query;        
		return $this;
	}

    /**
     * Sets the Host header value
     *
     * @param string $host
     * @param integer|null $port
     * @return void
     * @throws \InvalidArgumentException $host or $port is not valid
     */
    private function setHostHeader(string $host = '', ?int $port = null, bool $update = false): void
    {
        $host = $host === '' ? $this->uri->getHost() : $host;

        if ($host === '') {
            $result = ''; 
        } elseif (\preg_match('/^'.Uri::HST.'+$/i', $host)) { 

            $result = $host; 
            $port   = \is_null($port) ? $this->uri->getPort() : $port;

            if (\is_null($port) || (isset(Uri::$schemes[$port]) && Uri::$schemes[$port] === $this->uri->getScheme())) {
                $result .= '';
            } elseif ($port > 0 && 65535 >= $port) {
                $result .= ':'.$port;
            } else {
                throw new \InvalidArgumentException($port.' is not a valid port number');
            }  

        } else {
            throw new \InvalidArgumentException($host.' is not a valid host');
        }

        $this->setHeader('Host', $result, $update);
    }

}