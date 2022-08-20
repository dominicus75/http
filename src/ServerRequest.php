<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\{RequestInterface, ServerRequestInterface, UploadedFileInterface, UriInterface, StreamInterface};

/**
 * Representation of an incoming, server-side HTTP request.
 *
 * Per the HTTP specification, this class includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
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
     * The constructor method. Creates a new ServerRequest instance.
     *
     * @param string $version the HTTP protocol version as string
     * @param string $method the HTTP method name
     * @param array $headers the HTTP headers
     * @param string|UriInterface|null|null $uri the requested URI
     * @param string|StreamInterface|null|null $body the request body
     */
    public function __construct(
        string $version = '1.1',
        string $method  = '',
        array $headers  = [],
        string|UriInterface|null $uri = null,
        string|StreamInterface|null $body = null
    ) {
        parent::__construct($version, $method, $headers, $uri, $body);
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
    public function withCookieParams(array $cookies): ServerRequest
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
    public function withQueryParams(array $query): ServerRequest
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
    public function withUploadedFiles(array $uploadedFiles): ServerRequest
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
    public function withParsedBody($data): ServerRequest
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
    public function withAttribute($name, $value): ServerRequest
    {
        return $this;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute(string $name): ServerRequest
    {
        return $this;
    }

    #####################################
    # Protected, non-standard functions #
    #####################################

	/**
	 * Sets server property.
	 * 
	 * @param array $server If this array is empty, this method sets property 
     * from PHP's $_SERVER superglobal.
	 * @return ServerRequest
	 */
	private function setServer(array $server = []): self {
		$this->server = $server;
		return $this;
	}

	/**
	 * Sets cookie property.
	 * 
	 * @param array $cookie If this array is empty, this method sets property 
     * from PHP's $_COOKIE superglobal.
	 * @return ServerRequest
	 */
	private function setCookie(array $cookie = []): self {
		$this->cookie = $cookie;
		return $this;
	}

	/**
	 * Sets get property.
	 * 
	 * @param array $get If this array is empty, this method sets property
     * from PHP's $_GET superglobal.
	 * @return ServerRequest
	 */
	private function setGet(array $get = []): self {
		$this->get = $get;
		return $this;
	}

	/**
	 * Sets files property.
	 * 
	 * @param array $files If this array is empty, this method sets property
     * from PHP's $_FILES superglobal.
	 * @return ServerRequest
	 */
	private function setFiles(array $files = []): self {
		$this->files = $files;
		return $this;
	}

	/**
	 * Sets post (PSR-7: "parsed body") property.
	 * 
	 * @param array|StreamInterface $post If this argument is an array and is empty, 
     * this method sets property from PHP's $_POST superglobal.
	 * @return ServerRequest
	 */
	private function setPost(array|StreamInterface $post = []): self {
		$this->post = $post;
		return $this;
	}

	/**
	 * Sets an attribute derived from the request.
	 * 
     * @param string $name Attribute's name
	 * @param mixed $attribute Attrinute's value
	 * @return ServerRequest
	 */
	private function setAttribute(string $name, mixed $attribute): self {
		$this->attributes[$name] = $attribute;
		return $this;
	}

}