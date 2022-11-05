<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{ServerRequestInterface, StreamInterface, UriInterface};

/**
 * Representation of an incoming, server-side HTTP request.
 *
 */
class ServerRequest extends Request implements ServerRequestInterface
{

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
     * @param string $method the HTTP method name
     * @param string|UriInterface $uri the requested URI
     * @param string $version the HTTP protocol version as string
     * @param array $headers the HTTP headers
     * @param string|StreamInterface $body the request body
     * @return self
     * @throws \InvalidArgumentException if
     *  - if $method is not a valid HTTP method,
     *  - if $uri string is invalid
     *  - if HTTP protocol $version is not valid
     *  - if a header name is not valid
     *  - if a header value is not valid
     *  - if writing of body to stream fails
     */
    public function __construct(
        string $method  = '',
        string|UriInterface $uri,
        string $version = '1.1',
        array $headers  = [],
        string|StreamInterface $body = '',
    ) {
        try {
            parent::__construct($method, $uri, $version, $headers, $body);
        } catch (\InvalidArgumentException $e) { throw $e; }
    }

    ##########################
    # PSR-7 Public interface #
    ##########################

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
	 * Sets server (PSR-7: "server params") property.
	 * 
	 * @param array $server If this array is empty, this method sets property 
     * from PHP's $_SERVER superglobal.
	 * @return Request
	 */
	private function setServer(array $server = []): self 
    {
		$this->server = !empty($server) ? $server : $_SERVER;
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
		$this->cookie = !empty($cookie) ? $cookie : $_COOKIE;
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
		$this->get = !empty($get) ? $get : $_GET;
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
        $files = !empty($files) ? $files : $_FILES;
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
		$this->post = !empty($post) ? $post : $_POST;
		return $this;
	}

	/**
	 * Sets an attribute derived from the request.
	 * 
     * @param string $name Attribute's name
	 * @param mixed $value Attrinute's value
     * @param bool $update update exists attribute or not
	 * @return Request
	 */
	private function setAttribute(string $name, mixed $value, bool $update = false): self 
    {
        if ((isset($this->attributes[$name]) && $update) xor !isset($this->attributes[$name])) {
            $this->attributes[$name] = $value;
        }
        return $this;
	}

}