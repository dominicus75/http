<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{ServerRequestInterface, StreamInterface, UriInterface};

/**
 * Representation of an incoming, server-side HTTP request.
 *
 */
class Request extends AbstractMessage implements ServerRequestInterface
{

    /**
     * The request target, usually a URL, or the absolute path of the protocol, port,
     * and domain are usually characterized by the request context. The format of this
     * request target varies between different HTTP methods. 
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
     * Server and execution environment information, from PHP's $_SERVER superglobal.
     * $_SERVER is an array containing information such as headers, paths, and script 
     * locations. 
     *
     * @var array
     */
    private array $server = [];

    /**
     * An associative array of variables passed to the current script via HTTP Cookies.
     * These are from PHP's $_COOKIE superglobal. 
     *
     * @var array
     */
    private array $cookie = [];

    /**
     * An associative array of variables passed to the current script via the URL 
     * parameters (query string). These are from PHP's $_GET superglobal.
     *
     * @var array
     */
    private array $get    = [];

    /**
     * An associative array of items uploaded to the current script via the HTTP
     * POST method. This property contains the upload metadata in a normalized 
     * tree, with each leaf an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * @var array
     */
    private array $files  = [];

    /**
     * If the request Content-Type is either application/x-www-form-urlencoded or 
     * multipart/form-data, and the request method is POST, this property contains
     * the contents of $_POST superglobal. A null value indicates the absence of 
     * body content.
     *
     * @var array|null|object
     */
    private array|null|object $post = null;

    /**
     * Attributes derived from the request.
     *
     * @var array
     */
    private array $attributes = [];

    /**
     * The constructor method. Creates a new Request instance.
     *
     * @param string $version the HTTP protocol version as string
     * @param string $method the HTTP method name
     * @param array $headers the HTTP headers
     * @param string|UriInterface|null|null $uri the requested URI
     * @param string|StreamInterface|null|null $body the request body
     * @return self
     * @throws \InvalidArgumentException if
     *  - HTTP protocol $version is not valid
     *  - $method is not valid HTTP method,
     *  - a header name is not valid.
     */
    public function __construct(
        string $version = '1.1',
        string $method  = '',
        array $headers  = [],
        string|UriInterface|null $uri = null,
        string|StreamInterface|null $body = null
    ) {
        try {
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
            parent::__construct($version, $headers, $body);
            $this->setMethod($method);
            if ($uri instanceof UriInterface) {
                $this->uri = $uri;
            } else {
                try {
                    $this->uri = new Uri($uri);
                } catch (\InvalidArgumentException $e) { throw $e; }
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
        $reqHost    = $clone->getHeaderLine('Host');
        $uriHost    = $clone->getUri()->getHost();

        if ((!$preserveHost xor ($preserveHost && empty($reqHost))) && !empty($uriHost)) {
            $clone->setHeader('Host', $uriHost, true);
        }

        return $clone;
    }

    /**
     * Retrieve server parameters.
     *
     * @return array
     */
    public function getServerParams(): array { return $this->server; }

    /**
     * Retrieves cookies sent by the client to the server.
     *
     * @return array
     */
    public function getCookieParams(): array { return $this->cookie; }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies): Request
    {
        return $this;
    }

    /**
     * Retrieves the deserialized query string arguments, if any.
     *
     * @return array
     */
    public function getQueryParams(): array { return $this->get; }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query Array of query string arguments, typically from $_GET.
     * @return static
     */
    public function withQueryParams(array $query): Request
    {
        return $this;
    }

    /**
     * Retrieve upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * @return array An array tree of UploadedFileInterface instances; 
     * an empty array, if no data is present.
     */
    public function getUploadedFiles(): array { return $this->files; }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles): Request
    {
        return $this;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * @return null|array|object The deserialized body parameters, if any.
     */
    public function getParsedBody(): null|array|object { return $this->post; }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object $data The deserialized body data. 
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is provided.
     */
    public function withParsedBody($data): Request
    {
        return $this;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes(): array { return $this->attributes; }

    /**
     * Retrieve a single derived request attribute.
     *
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     * 
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value): Request
    {
        return $this;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute(string $name): Request
    {
        return $this;
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
        $method = !empty($method) ? $method : $_SERVER['REQUEST_METHOD'];

        if (\preg_match('/^(options|get|head|put|post|delete|patch)$/is', $method)) {
            $this->method = $method;
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
        $this->requestTarget  = empty($path) ? '/' : $path;
        $this->requestTarget .= empty($query) ? '' : '?'.$query;        
		return $this;
	}


	/**
	 * Sets server (PSR-7: "server params") property.
	 * 
	 * @param array $server If this array is empty, this method sets property 
     * from PHP's $_SERVER superglobal.
	 * @return Request
	 */
	private function setServer(array $server = []): self 
    {
		$this->server = $server;
		return $this;
	}

	/**
	 * Sets cookie (PSR-7: "cookie params") property.
	 * 
	 * @param array $cookie If this array is empty, this method sets property 
     * from PHP's $_COOKIE superglobal.
	 * @return Request
	 */
	private function setCookie(array $cookie = []): self 
    {
		$this->cookie = $cookie;
		return $this;
	}

	/**
	 * Sets get (PSR-7: "query params") property.
	 * 
	 * @param array $get If this array is empty, this method sets property
     * from PHP's $_GET superglobal.
	 * @return Request
	 */
	private function setGet(array $get = []): self 
    {
		$this->get = $get;
		return $this;
	}

	/**
	 * Sets files (PSR-7: "uploaded files") property.
	 * 
	 * @param array $files If this array is empty, this method sets property
     * from PHP's $_FILES superglobal.
	 * @return Request
	 */
	private function setFiles(array $files = []): self 
    {
		$this->files = $files;
		return $this;
	}

	/**
	 * Sets post (PSR-7: "parsed body") property.
	 * 
	 * @param array|StreamInterface $post If this argument is an array and is empty, 
     * this method sets property from PHP's $_POST superglobal.
	 * @return Request
	 */
	private function setPost(array|StreamInterface $post = []): self 
    {
		$this->post = $post;
		return $this;
	}

	/**
	 * Sets an attribute derived from the request.
	 * 
     * @param string $name Attribute's name
	 * @param mixed $value Attrinute's value
	 * @return Request
	 */
	private function setAttribute(string $name, mixed $value): self 
    {
        $this->attributes[$name] = $value;
        return $this;
	}

	/**
	 * Updates an attribute derived from the request.
	 * 
     * @param string $name Attribute's name
	 * @param mixed $value Attrinute's value
	 * @return Request
	 */
	private function updateAttribute(string $name, mixed $value): self 
    {
        $this->attributes[$name] = $value;
        return $this;
	}

}
