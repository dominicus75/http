<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Dominicus75\Psr7\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @covers Dominicus75\Psr7\Uri
 * Based on Tobias Nyholm's work
 * @see https://github.com/Nyholm/psr7/blob/master/tests/UriTest.php
 */
class UriTest extends TestCase
{
    private array $string_input = [
        0 => [
            'uri'    => 'https://john.doe:pa$$w0rD@127.0.1.1/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
            'scheme' => 'https',
            'user'   => 'john.doe',
            'pass'   => 'pa$$w0rD',
            'host'   => '127.0.1.1',
            'port'   => 443,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => 'töredék'
        ],
        1 => [
            'uri'    => 'https://john.doe:pa$$w0rD@[fe80::202:b3ff:fe1e:8329]:65321/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
            'scheme' => 'https',
            'user'   => 'john.doe',
            'pass'   => 'pa$$w0rD',
            'host'   => '[fe80::202:b3ff:fe1e:8329]',
            'port'   => 65321,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => 'töredék'
        ],
        2 => [
            'uri'    => 'https://john.doe:pa$$w0rD@en.localhost.info:456/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
            'scheme' => 'https',
            'user'   => 'john.doe',
            'pass'   => 'pa$$w0rD',
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => 'töredék'
        ],
        3 => [
            'uri'    => 'http://127.0.1.1/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => '127.0.1.1',
            'port'   => 80,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => 'töredék'
        ],
        4 => [
            'uri'    => 'http://[::1]:65321/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => '[::1]',
            'port'   => 65321,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => 'töredék'
        ],
        5 => [
            'uri'    => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => 'töredék'
        ],
        6 => [
            'uri'    => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=foo&query1=baz#fragment',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=foo&query1=baz',
            'fragm'  => 'fragment'
        ],
        7 => [
            'uri'    => 'http://en.localhost.info:456/directory/subdirectory/file.html',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => null,
            'fragm'  => null
        ],
        8 => [
            'uri'    => '//en.localhost.info/directory/subdirectory/file.html',
            'scheme' => '',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => null,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => null,
            'fragm'  => null
        ]
    ];

    private array $assertions = [
        0 => [
            'toString'     => 'https://john.doe:pa$$w0rD@127.0.1.1/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'https',
            'getUserInfo'  => 'john.doe:pa$$w0rD',
            'getAuthority' => 'john.doe:pa$$w0rD@127.0.1.1',
            'getHost'      => '127.0.1.1',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ],
        1 => [
            'toString'     => 'https://john.doe:pa$$w0rD@[fe80::202:b3ff:fe1e:8329]:65321/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'https',
            'getUserInfo'  => 'john.doe:pa$$w0rD',
            'getAuthority' => 'john.doe:pa$$w0rD@[fe80::202:b3ff:fe1e:8329]:65321',
            'getHost'      => '[fe80::202:b3ff:fe1e:8329]',
            'getPort'      => 65321,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ],
        2 => [
            'toString'     => 'https://john.doe:pa$$w0rD@en.localhost.info:456/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'https',
            'getUserInfo'  => 'john.doe:pa$$w0rD',
            'getAuthority' => 'john.doe:pa$$w0rD@en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ],
        3 => [
            'toString'     => 'http://127.0.1.1/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => '127.0.1.1',
            'getHost'      => '127.0.1.1',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ],
        4 => [
            'toString'     => 'http://[::1]:65321/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => '[::1]:65321',
            'getHost'      => '[::1]',
            'getPort'      => 65321,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ],
        5 => [
            'toString'     => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ],
        6 => [
            'toString'     => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=foo&query1=baz#fragment',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=foo&query1=baz',
            'getFragment'  => 'fragment'
        ],
        7 => [
            'toString'     => 'http://en.localhost.info:456/directory/subdirectory/file.html',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => '',
            'getFragment'  => ''
        ],
        8 => [
            'toString'     => '//en.localhost.info/directory/subdirectory/file.html',
            'getScheme'    => '',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info',
            'getHost'      => 'en.localhost.info',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => '',
            'getFragment'  => ''
        ]
    ];

    private array $global_input = [
        0 => [
            'uri'    => 'https://john.doe:pa$$w0rD@127.0.1.1/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'https',
            'user'   => 'john.doe',
            'pass'   => 'pa$$w0rD',
            'host'   => '127.0.1.1',
            'port'   => 443,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ],
        1 => [
            'uri'    => 'https://john.doe:pa$$w0rD@[fe80::202:b3ff:fe1e:8329]:65321/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'https',
            'user'   => 'john.doe',
            'pass'   => 'pa$$w0rD',
            'host'   => '[fe80::202:b3ff:fe1e:8329]',
            'port'   => 65321,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ],
        2 => [
            'uri'    => 'https://john.doe:pa$$w0rD@en.localhost.info:456/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'https',
            'user'   => 'john.doe',
            'pass'   => 'pa$$w0rD',
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ],
        3 => [
            'uri'    => 'http://127.0.1.1/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => '127.0.1.1',
            'port'   => 80,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ],
        4 => [
            'uri'    => 'http://[::1]:65321/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => '[::1]',
            'port'   => 65321,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ],
        5 => [
            'uri'    => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ],
        6 => [
            'uri'    => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=foo&query1=baz',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=foo&query1=baz',
            'fragm'  => null
        ],
        7 => [
            'uri'    => 'http://en.localhost.info:456/directory/subdirectory/file.html',
            'scheme' => 'http',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => 456,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => null,
            'fragm'  => null
        ],
        8 => [
            'uri'    => '//en.localhost.info/directory/subdirectory/file.html',
            'scheme' => '',
            'user'   => null,
            'pass'   => null,
            'host'   => 'en.localhost.info',
            'port'   => null,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => null,
            'fragm'  => null
        ]
    ];

    private array $global_assertions = [
        0 => [
            'toString'     => 'https://john.doe:pa$$w0rD@127.0.1.1/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'https',
            'getUserInfo'  => 'john.doe:pa$$w0rD',
            'getAuthority' => 'john.doe:pa$$w0rD@127.0.1.1',
            'getHost'      => '127.0.1.1',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ],
        1 => [
            'toString'     => 'https://john.doe:pa$$w0rD@[fe80::202:b3ff:fe1e:8329]:65321/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'https',
            'getUserInfo'  => 'john.doe:pa$$w0rD',
            'getAuthority' => 'john.doe:pa$$w0rD@[fe80::202:b3ff:fe1e:8329]:65321',
            'getHost'      => '[fe80::202:b3ff:fe1e:8329]',
            'getPort'      => 65321,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ],
        2 => [
            'toString'     => 'https://john.doe:pa$$w0rD@en.localhost.info:456/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'https',
            'getUserInfo'  => 'john.doe:pa$$w0rD',
            'getAuthority' => 'john.doe:pa$$w0rD@en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ],
        3 => [
            'toString'     => 'http://127.0.1.1/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => '127.0.1.1',
            'getHost'      => '127.0.1.1',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ],
        4 => [
            'toString'     => 'http://[::1]:65321/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => '[::1]:65321',
            'getHost'      => '[::1]',
            'getPort'      => 65321,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ],
        5 => [
            'toString'     => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ],
        6 => [
            'toString'     => 'http://en.localhost.info:456/directory/subdirectory/file.html?query0=foo&query1=baz',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=foo&query1=baz',
            'getFragment'  => ''
        ],
        7 => [
            'toString'     => 'http://en.localhost.info:456/directory/subdirectory/file.html',
            'getScheme'    => 'http',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info:456',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => '',
            'getFragment'  => ''
        ],
        8 => [
            'toString'     => '//en.localhost.info/directory/subdirectory/file.html',
            'getScheme'    => '',
            'getUserInfo'  => '',
            'getAuthority' => 'en.localhost.info',
            'getHost'      => 'en.localhost.info',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => '',
            'getFragment'  => ''
        ]
    ];


    private array $invalid_uris = [
        'https:/john.doe:pa$$w0rD@127:0:1:1:70000/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
        'http://',
        'http://host:with:colon',
        'http://example.com:-1',
        'http://example.com:0'
    ];

    protected function setUp(): void
    {
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['PHP_AUTH_USER']  = 'gipsz.jakab';
        $_SERVER['PHP_AUTH_PW']    = 'pa$$w0rD';
        $_SERVER['HTTP_HOST']      = 'hu.wikipedia.org';
        $_SERVER['SERVER_PORT']    = 80;
        $_SERVER['REQUEST_URI']    = '/directory/subdirectory/file.html';
        $_SERVER['REQUEST_URI']   .= '?query0=iz%C3%A9&query1=bigy%C3%B3';
        $_SERVER['QUERY_STRING']   = 'query0=iz%C3%A9&query1=bigy%C3%B3';

        $this->global_input[9] = [
            'uri'    => 'http://gipsz.jakab:pa$$w0rD@hu.wikipedia.org/directory/subdirectory/file.html?query0=izé&query1=bigyó',
            'scheme' => 'http',
            'user'   => 'gipsz.jakab',
            'pass'   => 'pa$$w0rD',
            'host'   => 'hu.wikipedia.org',
            'port'   => 80,
            'path'   => '/directory/subdirectory/file.html',
            'query'  => 'query0=izé&query1=bigyó',
            'fragm'  => null
        ];
        $this->global_assertions[9] = [
            'toString'     => 'http://gipsz.jakab:pa$$w0rD@hu.wikipedia.org/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3',
            'getScheme'    => 'http',
            'getUserInfo'  => 'gipsz.jakab:pa$$w0rD',
            'getAuthority' => 'gipsz.jakab:pa$$w0rD@hu.wikipedia.org',
            'getHost'      => 'hu.wikipedia.org',
            'getPort'      => null,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => ''
        ];
    }

    public function testCreateUriFromGivenString()
    {
        foreach ($this->string_input as $index => $testcase) {
            $uri = new Uri($testcase['uri']);
            $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
            $actual = [
                'toString'     => (string) $uri,
                'getScheme'    => $uri->getScheme(),
                'getUserInfo'  => $uri->getUserInfo(),
                'getAuthority' => $uri->getAuthority(),
                'getHost'      => $uri->getHost(),
                'getPort'      => $uri->getPort(),
                'getPath'      => $uri->getPath(),
                'getQuery'     => $uri->getQuery(),
                'getFragment'  => $uri->getFragment()    
            ];
            foreach ($actual as $function => $return) { 
                $this->assertEquals($this->assertions[$index][$function], $return, $function.", ".$index); 
            }
        }
    }

    public function testCreateUriFromSuperglobals()
    {
        foreach ($this->global_input as $index => $testcase) {

            $_SERVER['REQUEST_SCHEME'] = $testcase['scheme'];
            $_SERVER['PHP_AUTH_USER']  = $testcase['user'];
            $_SERVER['PHP_AUTH_PW']    = $testcase['pass'];
            $_SERVER['HTTP_HOST']      = $this->global_assertions[$index]['getHost'];
            $_SERVER['SERVER_PORT']    = $testcase['port'];
            $_SERVER['REQUEST_URI']    = $this->global_assertions[$index]['getPath'];
            $_SERVER['REQUEST_URI']   .= !empty($this->global_assertions[$index]['getQuery']) ? '?'.$this->global_assertions[$index]['getQuery'] : '';
            $_SERVER['QUERY_STRING']   = !empty($this->global_assertions[$index]['getQuery']) ? $this->global_assertions[$index]['getQuery'] : '';

            $uri    = new Uri(null);
            $actual = [
                'toString'     => (string) $uri,
                'getScheme'    => $uri->getScheme(),
                'getUserInfo'  => $uri->getUserInfo(),
                'getAuthority' => $uri->getAuthority(),
                'getHost'      => $uri->getHost(),
                'getPort'      => $uri->getPort(),
                'getPath'      => $uri->getPath(),
                'getQuery'     => $uri->getQuery(),
                'getFragment'  => $uri->getFragment()    
            ];

            $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);

            foreach ($actual as $function => $return) {
                $this->assertEquals($this->global_assertions[$index][$function], $return, $function.", ".$index); 
            }

        }

    }

    public function testInvalidUrisThrowException()
    {
        foreach ($this->invalid_uris as $uri) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($uri.' is not a valid URI');
            new Uri($uri);
        }
    }

    public function testPortMustBeValid()
    {
        for ($port = 70000; $port < 70050; $port++) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($port.' is not a valid port number');
            (new Uri('https://example.com:456/path'))->withPort($port);
        }

        for ($port = 0; $port >= -10; $port--) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($port.' is not a valid port number');
            (new Uri('https://example.com/path'))->withPort($port);
        }
    }

    public function testWithPortCannotBeNegative()
    {
        $uri = new Uri();
        for ($port = -1; $port >= -10; $port--) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($port.' is not a valid port number');
            $uri->withPort($port);
        }
    }

    public function testSchemeMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheme must be a string');
        $uri = new Uri();
        $uri->withScheme([]);
        $uri->withScheme(1984);
        $uri->withScheme(true);
    }

    public function testUserInfoMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Userinfo must be a string');
        $uri = new Uri();
        $uri->withUserInfo('user', false);
        $uri->withUserInfo(true, 1234);
        $uri->withUserInfo(123, false);
    }

    public function testHostMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Host must be a string');
        $uri = new Uri();
        $uri->withHost([]);
        $uri->withHost(1234);
        $uri->withHost(false);
    }

    public function testPortMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Port must be an integer or null');
        $uri = new Uri();
        $uri->withPort([]);
        $uri->withPort('1234');
        $uri->withPort(false);
    }

    public function testPathMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path must be a string');
        $uri = new Uri();
        $uri->withPath([]);
        $uri->withPath(1234);
        $uri->withPath(false);
    }

    public function testQueryMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query must be a string');
        $uri = new Uri();
        $uri->withQuery([]);
        $uri->withQuery(1234);
        $uri->withQuery(false);
    }

    public function testFragmentMustHaveCorrectType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fragment must be a string');
        $uri = new Uri();
        $uri->withFragment([]);
        $uri->withFragment(1234);
        $uri->withFragment(false);
    }

    public function testSchemeIsNormalizedToLowercase()
    {
        $uri = new Uri('HTTP://example.com');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('http://example.com', (string) $uri);

        $uri->withScheme('HTTP');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('http://example.com', (string) $uri);
    }

    public function testHostIsNormalizedToLowercase()
    {
        $uri = new Uri('//eXaMpLe.CoM');

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('//example.com', (string) $uri);

        $uri->withHost('eXaMpLe.CoM');

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('//example.com', (string) $uri);
    }

    public function testPortIsNullIfStandardPortForScheme()
    {
        // HTTPS standard port
        $uri = new Uri('https://example.com:443');
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());

        $uri = (new Uri('https://example.com'))->withPort(443);
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());

        // HTTP standard port
        $uri = new Uri('http://example.com:80');
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());

        $uri = (new Uri('http://example.com'))->withPort(80);
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());
    }

    public function testPortIsReturnedIfSchemeUnknown()
    {
        $uri = (new Uri('//example.com'))->withPort(80);
        $this->assertSame(80, $uri->getPort());
        $this->assertSame('example.com:80', $uri->getAuthority());
    }

    public function testStandardPortIsNullIfSchemeChanges()
    {
        $uri = new Uri('http://example.com:443');
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame(443, $uri->getPort());

        $uri = $uri->withScheme('https');
        $this->assertNull($uri->getPort());
    }

    public function testPortCanBeRemoved()
    {
        $uri = (new Uri('http://example.com:8080'))->withPort(null);

        $this->assertNull($uri->getPort());
        $this->assertSame('http://example.com', (string) $uri);
    }

    public function testAuthorityWithUserInfoButWithoutHost()
    {
        $uri = (new Uri())->withUserInfo('user', 'pass');

        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('', $uri->getAuthority());
    }

    public function testWithPathEncodesProperly()
    {
        $uri = (new Uri())->withPath('/baz?#€/b%61r');
        // Query and fragment delimiters and multibyte chars are encoded.
        $this->assertSame('/baz%3F%23%E2%82%AC/b%61r', $uri->getPath());
        $this->assertSame('/baz%3F%23%E2%82%AC/b%61r', (string) $uri);
    }

    public function testDefaultReturnValuesOfGetters()
    {
        $uri = new Uri();

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
    }

    public function testImmutability()
    {
        $uri = new Uri();

        $this->assertNotSame($uri, $uri->withScheme('https'));
        $this->assertNotSame($uri, $uri->withUserInfo('user', 'pass'));
        $this->assertNotSame($uri, $uri->withHost('example.com'));
        $this->assertNotSame($uri, $uri->withPort(8080));
        $this->assertNotSame($uri, $uri->withPath('/path/123'));
        $this->assertNotSame($uri, $uri->withQuery('q=abc'));
        $this->assertNotSame($uri, $uri->withFragment('test'));
    }

    public function testUtf8Host()
    {
        $uri = new Uri('http://ουτοπία.δπθ.gr/');
        $this->assertSame('%CE%BF%CF%85%CF%84%CE%BF%CF%80%CE%AF%CE%B1.%CE%B4%CF%80%CE%B8.gr', $uri->getHost());
        $new = $uri->withHost('程式设计.com');
        $this->assertSame('%E7%A8%8B%E5%BC%8F%E8%AE%BE%E8%AE%A1.com', $new->getHost());

        $testDomain = 'παράδειγμα.δοκιμή';
        $uriEncoded = '%CF%80%CE%B1%CF%81%CE%AC%CE%B4%CE%B5%CE%B9%CE%B3%CE%BC%CE%B1.%CE%B4%CE%BF%CE%BA%CE%B9%CE%BC%CE%AE';
        $uri = (new Uri())->withHost($testDomain);
        $this->assertSame($uriEncoded, $uri->getHost());
        $this->assertSame('//' . $uriEncoded, (string) $uri);
    }

}