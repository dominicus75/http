<?php declare(strict_types=1);

namespace Dominicus75\Psr7;

use Psr\Http\Message\UriInterface;

/**
 * Value object representing a URI.
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class Uri implements UriInterface
{
    /**
     * @var array The enabled schemes and their ports.
     */
    public static $schemes = [
        80  => 'http',
        443 => 'https'
    ];

    /**
     * @var string unreserved characters according to standard STD 66 (RFC 3986)
     * Characters that are allowed in a URI but do not have a reserved
     * purpose are called unreserved.
     * @see https://www.rfc-editor.org/rfc/rfc3986.html#section-2.3
     */
    private const UNRESERV = '\w\-\.~';

    /**
     * @var string main delimiter characters according to standard STD 66 (RFC 3986)
     * A subset of the reserved characters is used as delimiters of the generic URI components
     * @see https://www.rfc-editor.org/rfc/rfc3986.html#section-2.2
     */
    public const GENDELIM = '\:\/\?#\[\]@';

    /**
     * @var string sub-delimiter characters according to standard STD 66 (RFC 3986)
     * A subset of the reserved characters is used as subcomponent delimiters within the component
     * @see https://www.rfc-editor.org/rfc/rfc3986.html#section-2.2
     */
    public const SUBDELIM = '!\$&\'\(\)\*\+,;=';
    
    /** @var string Pattern of basic characters */
    public const BASECHAR = self::UNRESERV.self::SUBDELIM.'%';

    /** @var string Pattern of hexadecimal number */
    public const HEX = "A-Fa-f0-9";

    /** @var string Pattern of percent-encoded hexadecimal number */
    public const PCE = '%['.self::HEX.']{2}';

    /** @var string Pattern of Ipv4 addresses */
    public const IP4 = "[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}";

    /** @var string Pattern of Ipv6 addresses */
    public const IP6 = "(\[[".self::HEX."\:]{3,39}\])";

    /** @var string Pattern of domain */
    public const REG = '(['.self::BASECHAR.']+|'.self::PCE.')';
 
    /** @var string Pattern of URI userinfo component */
    public const USR = "([".self::BASECHAR."\:]+|".self::PCE.")";

    /** @var string Pattern of URI host component */
    public const HST = "(".self::IP4."|".self::IP6."|".self::REG."+)";

    /** @var string Pattern of URI path component */
    public const PTH = "([".self::BASECHAR."\:@\/]+|".self::PCE.")";

    /** @var string Pattern of URI query string component */
    public const QRY = "([".self::BASECHAR."]+|".self::PCE.")";

    /** @var string Pattern of URI fragment component */
    public const FRG = "([".self::BASECHAR."]+|".self::PCE.")";

    public static $encoder = [
        'wholeuri' => '/([^'.self::BASECHAR.self::GENDELIM.']+|(?!'.self::PCE.'))/i',
        'userinfo' => '/([^'.self::BASECHAR.'\:]+|(?!'.self::PCE.'))/i',
        'host'     => '/([^'.self::BASECHAR.'\[\]\:]+|(?!'.self::PCE.'))/i',
        'path'     => '/([^'.self::BASECHAR.'\:@\/]+|(?!'.self::PCE.'))/i',
        'query'    => '/([^'.self::BASECHAR.']+|(?!'.self::PCE.'))/i',
        'fragment' => '/([^'.self::BASECHAR.']+|(?!'.self::PCE.'))/i'
    ];

    /** @var string The scheme component of the URI. */
    private string $scheme = '';

    /** @var string The percent-encoded user information component of the URI. */
    private string $userInfo = '';

    /** @var string The host component of the URI. */
    private string $host = '';

    /** @var int|null The port component of the URI. */
    private int|null $port = null;

    /** @var string The percent-encoded path component of the URI. */
    private string $path = '';

    /** @var string The percent-encoded query string of the URI. */
    private string $query = '';

    /** @var string The percent-encoded fragment component of the URI. */
    private string $fragment = '';

    /**
     * Creates a new Uri instance
     * 
     * @param string|null $uri
     * - if $uri is empty string: creates an empty instance
     * - if $uri is not empty: creates an instance based on $uri
     * - if $uri is null: cretaes a new instance from superglobals
     * @return Uri
     * @throws \InvalidArgumentException for invalid URI.
     */
    public function __construct(?string $uri = '')
    {
        if (\is_null($uri)) {
            $components['scheme']   = $_SERVER['REQUEST_SCHEME'] ?? '';
            $components['user']     = $_SERVER['PHP_AUTH_USER']  ?? '';
            $components['pass']     = $_SERVER['PHP_AUTH_PW']    ?? '';
            $components['host']     = $_SERVER['HTTP_HOST']      ?? ($_SERVER['SERVER_NAME'] ?? '');
            $components['port']     = $_SERVER['SERVER_PORT']    ?? null;
            $components['path']     = $_SERVER['REQUEST_URI']    ? \explode('?', $_SERVER['REQUEST_URI'])[0] : ''; 
            $components['query']    = $_SERVER['QUERY_STRING']   ?? '';
            $components['fragment'] = '';
        } elseif (\is_string($uri)) {
            $components = empty($uri) ? [] : \parse_url($uri);
        } 

        if ($components === false || !\is_array($components)) {
            throw new \InvalidArgumentException($uri.' is not a valid URI');
        }

        try {
            $this->setScheme($components['scheme'] ?? '');       
            $this->setUserInfo($this->compileUserInfo(($components['user'] ?? ''), ($components['pass'] ?? null)));
            $this->setHost($components['host'] ?? '');
            $this->setPort(isset($components['port']) ? (int) $components['port'] : null);
            $this->setPath($components['path'] ?? '');
            $this->setQuery($components['query'] ?? '');
            $this->setFragment($components['fragment'] ?? '');
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException($te->getMessage());
        } catch (\InvalidArgumentException $e) { 
            throw $e;
        } 
    }

    ##########################
    # PSR-7 Public interface #
    ##########################

    /**
     * Retrieve the scheme component of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme. If no scheme is present, this method 
     * returns an empty string. The value returned MUST be normalized to 
     * lowercase, per RFC 3986 Section 3.1.
     */
    public function getScheme(): string { return $this->scheme; }

    /**
     * Retrieve the authority component of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     * If no authority information is present, this method returns an empty string.
     */
    public function getAuthority(): string 
    {
        if (empty($this->host)) { return ''; }
        $authority  = !empty($this->userInfo) ? $this->userInfo.'@' : '';
        $authority .= $this->host;
        $authority .= !\is_null($this->port) ? ":{$this->port}" : '';
        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * @return string The URI user information, in "username[:password]" format.
     * If no user information is present, this method MUST return an empty
     * string. The trailing "@" character is not part of the user information.
     */
    public function getUserInfo(): string { return $this->userInfo; }

    /**
     * Retrieve the host component of the URI.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host. If no host is present, this method 
     * returns an empty string. The value returned MUST be normalized 
     * to lowercase, per RFC 3986 Section 3.2.2.
     */
    public function getHost(): string { return $this->host; }

    /**
     * Retrieve the port component of the URI.
     *
     * @return null|int The URI port. If no port is present, but a scheme is 
     * present, this method returns the standard port for that scheme. If no 
     * port is present, and no scheme is present, this method returns a null value.
     */
    public function getPort(): ?int { return $this->port; }

    /**
     * Retrieve the path component of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path. The value returned MUST be percent-encoded.
     */
    public function getPath(): string { return $this->path; }

    /**
     * Retrieve the query string of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string. The value returned MUST be percent-encoded.
     * If no query string is present, this method returns an empty string. 
     * The leading "?" character is not part of the query. 
     */
    public function getQuery(): string { return $this->query; }

    /**
     * Retrieve the fragment component of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment. The value returned MUST be percent-encoded.
     * If no fragment is present, this method returns an empty string.
     * The leading "#" character is not part of the fragment.
     */
    public function getFragment(): string { return $this->fragment; }

    /**
     * Return an instance with the specified scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * An empty scheme is equivalent to removing the scheme.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme): Uri
    {
        try {
            if ($scheme === $this->scheme) { return $this; }
            $clone = clone $this;
            $clone->setScheme($scheme);
            $clone->setPort($clone->port);
            return $clone;
        } catch (\InvalidArgumentException $e) { 
            throw $e; 
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }
    }

    /**
     * Return an instance with the specified user information.
     *
     * @param string $user The user name to use for authority. An empty 
     * string for the user is equivalent to removing user information.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     * @throws \InvalidArgumentException for invalid datatype or invalid userinfo.
     */
    public function withUserInfo($user, $password = null): Uri
    {
        try {
            $userinfo = $this->compileUserInfo($user, $password);
            if ($this->userInfo === $this->encode($userinfo, 'userinfo')) { return $this; }
            $clone = clone $this;
            $clone->setUserInfo($userinfo);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Userinfo must be a string');
        } 
    }

    /**
     * Return an instance with the specified host.
     *
     * @param string $host The hostname to use with the new instance.
     * An empty host value is equivalent to removing the host.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid datatype or invalid hostname.
     */
    public function withHost($host): Uri 
    {
        try {
            if ($this->encode($host, 'host') === $this->host) { return $this; }
            $clone = clone $this;
            $clone->setHost($host);
            return $clone;
        } catch (\InvalidArgumentException $e) { 
            throw $e; 
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Host must be a string');
        }
    }

    /**
     * Return an instance with the specified port.
     *
     * @param null|int $port The port to use with the new instance; 
     * a null value removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid datatype or invalid port.
     */
    public function withPort($port): Uri
    {
        try {
            if ($port === $this->port) { return $this; }
            $clone = clone $this;
            $clone->setPort($port);
            return $clone;
        } catch (\InvalidArgumentException $e) { 
            throw $e; 
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Port must be an integer or null');
        }
    }

    /**
     * Return an instance with the specified path.
     *
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid datatype or invalid path.
     */
    public function withPath($path): Uri
    {
        try {
            if ($this->encode($path) === $this->path) { return $this; }
            $clone = clone $this;
            $clone->setPath($path);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Path must be a string');
        }
    }

    /**
     * Return an instance with the specified query string.
     *
     * @param string $query The query string to use with the new instance.
     * An empty query string value is equivalent to removing the query string.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid datatype or invalid query string.
     */
    public function withQuery($query): Uri
    {
        try {
            if ($this->encode($query, 'query') === $this->query) { return $this; }
            $clone = clone $this;
            $clone->setQuery($query);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Query must be a string');
        }
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * An empty fragment value is equivalent to removing the fragment.
     * @return static A new instance with the specified fragment.
     * @throws \InvalidArgumentException for invalid datatype or invalid fragment.
     */
    public function withFragment($fragment): Uri
    {
        try {
            if ($this->encode($fragment, 'fragment') === $this->fragment) { return $this; }
            $clone = clone $this;
            $clone->setFragment($fragment);
            return $clone;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\TypeError $te) {
            throw new \InvalidArgumentException('Fragment must be a string');
        }
    }

    /**
     * Return the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     */
    public function __toString(): string 
    { 
        $result  = !empty($this->scheme)         ? $this->scheme.':'          : '';
        $result .= !empty($this->getAuthority()) ? '//'.$this->getAuthority() : '';
        $result .= !empty($this->path)           ? $this->path                : '';
        $result .= !empty($this->query)          ? '?'.$this->query           : '';
        $result .= !empty($this->fragment)       ? '#'.$this->fragment        : '';
        return $result; 
    }

    ##########################
    # non-standard functions #
    ##########################

    /**
     * URL-encode according to RFC 3986
     *
     * @param string $string The string to be encoded. 
     * @return string Returns a string in which all non-alphanumeric characters except -_.~ 
     * have been replaced with a percent (%) sign followed by two hex digits. 
     */
    protected function encode(string $string, string $component = 'wholeuri'): string
    {
        if (!empty($string)) {
            $result = \preg_replace_callback(
                static::$encoder[$component],
                function ($matches) { return rawurlencode($matches[0]); },
                $string
            );
        } else { $result = $string; }
		return $result;
    }

    protected function compileUserInfo(string $user, ?string $pass = null): string
    {
        if (!empty($user) && isset($pass) && !empty($pass)) { 
            return $user.':'.$pass;
        } elseif (!empty($user)) {
            return $user;
        } else { 
            return '';
        }
    }

    /**
     * Sets the scheme component of the URI.
     * 
     * @param string $scheme 
     * @return Uri
     * @throws \InvalidArgumentException for invalid scheme.
     */
    protected function setScheme(string $scheme): self 
    {
        if (empty($scheme)) { 
            $this->scheme = '';
        } elseif (\preg_match('/^https?$/i', $scheme)) {
            $this->scheme = \strtolower($scheme);
        } else {
            throw new \InvalidArgumentException($scheme.' is not a valid scheme');
        }
        return $this;
    }
    
    /**
     * Sets the user information component of the URI.
     * 
     * @param string $user
     * @param string|null $pass
     * @return Uri
     * @throws \InvalidArgumentException for invalid userinfo.
     */
    protected function setUserInfo(string $userinfo): self 
    {
        if (empty($userinfo)) {
            $this->userInfo = '';
        } else {
            $userinfo = $this->encode($userinfo, 'userinfo');
            if (\preg_match('/^'.self::USR.'+$/i', $userinfo)) {
                $this->userInfo = $userinfo;
            } else {
                throw new \InvalidArgumentException($userinfo.' is not a valid userinfo component');
            }
        }
        return $this;
    }

    /**
     * Sets the host component of the URI.
     * 
     * @param string $host 
     * @return Uri
     * @throws \InvalidArgumentException for invalid host.
     */
    protected function setHost(string $host): self 
    {
        if (empty($host)) {
            $this->host = '';
        } else {
            $host = $this->encode(\strtr($host, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'host');
            if (\preg_match('/^'.self::HST.'+$/i', $host)) {
                $this->host = $host;
            } else {
                throw new \InvalidArgumentException($host.' is not a valid host component');
            }
        }
        return $this;
    }

    /**
     * Sets the port component of the URI.
     * 
     * @param int|null $port 
     * @return Uri
     * @throws \InvalidArgumentException for invalid port number.
     */
    protected function setPort(int|null $port): self
    {
        if (\is_null($port) || (isset(self::$schemes[$port]) && self::$schemes[$port] === $this->scheme)) {
            $this->port = null;
        } elseif ($port > 0 && 65535 >= $port) {
            $this->port = $port;
        } else {
            throw new \InvalidArgumentException($port.' is not a valid port number');
        }
        return $this;
    }

    /**
     * Sets the path component of the URI.
     * 
     * @param string $path 
     * @return Uri
     * @throws \InvalidArgumentException for invalid path.
     */
    protected function setPath(string $path): self 
    {
        if (empty($path)) {
            $this->path = '';
        } else {
            $path = $this->encode($path, 'path');
            if (\preg_match('/^'.self::PTH.'+$/i', $path)) {
                $this->path = $path;
            } else {
                throw new \InvalidArgumentException($path.' is not a valid path');
            }
        }
        return $this;
    }

    /**
     * Sets the query string component of the URI.
     * 
     * @param string $query 
     * @return Uri
     * @throws \InvalidArgumentException for invalid query strings.
     */
    protected function setQuery(string $query): self 
    {
        if (empty($query)) {
            $this->query = '';
        } else {
            $query = $this->encode($query, 'query');
            if (\preg_match(self::QRY, $query)) {
                $this->query = $query;
            } else {
                throw new \InvalidArgumentException($query.' is not a valid query string');
            }
        }
        return $this;
    }

    /**
     * Sets the fragment component of the URI.
     * 
     * @param string $fragment 
     * @return Uri
     * @throws \InvalidArgumentException for invalid fragment.
     */
    protected function setFragment(string $fragment): self 
    {
        if (empty($fragment)) {
            $this->fragment = '';
        } else {
            $fragment = $this->encode($fragment, 'fragment');
            if (\preg_match(self::FRG, $fragment)) {
                $this->fragment = $fragment;
            } else {
                throw new \InvalidArgumentException($fragment.' is not a valid fragment');
            }
        }
        return $this;
    }

}