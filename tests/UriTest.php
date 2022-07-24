<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Dominicus75\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{

    const ABS_IPV4_DEFTPORT_UTF8_HASALL  = 0;
    const ABS_IPV6_ELSEPORT_UTF8_HASALL  = 1;
    const ABS_DOMA_ELSEPORT_UTF8_HASALL  = 2;
    const REL_IPV4_DEFTPORT_UTF8_NOTAUT  = 3;
    const REL_IPV6_ELSEPORT_UTF8_NOTAUT  = 4;
    const REL_DOMA_ELSEPORT_UTF8_NOTAUT  = 5;
    const REL_DOMA_ELSEPORT_ASCI_NOTAUT  = 6;
    const REL_DOMA_ELSEPORT_ASCI_NOTAQF  = 7;
    const REL_DOMA_DEFTPORT_ASCI_NOTSAQF = 8;

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

    private array $invalid_uris = [
        'https:/john.doe:pa$$w0rD@127:0:1:1:70000/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
        'http://',
        'http://host:with:colon'
    ];


    public function testCreateUriFromGivenString()
    {
        foreach ($this->string_input as $index => $testcase) {
            $uri = new Uri($this->string_input[$index]['uri']);
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
        foreach ($this->string_input as $index => $testcase) {

            $_SERVER['REQUEST_SCHEME'] = $this->string_input[$index]['scheme'];
            $_SERVER['PHP_AUTH_USER']  = $this->string_input[$index]['user'];
            $_SERVER['PHP_AUTH_PW']    = $this->string_input[$index]['pass'];
            $_SERVER['HTTP_HOST']      = $this->assertions[$index]['getHost'];
            $_SERVER['SERVER_PORT']    = $this->string_input[$index]['port'];
            $_SERVER['REQUEST_URI']    = $this->assertions[$index]['getPath'];
            $_SERVER['REQUEST_URI']   .= !empty($this->assertions[$index]['getQuery']) ? '?'.$this->assertions[$index]['getQuery'] : '';
            $_SERVER['REQUEST_URI']   .= !empty($this->assertions[$index]['getFragment']) ? '#'.$this->assertions[$index]['getFragment'] : '';

            $uri    = new Uri();
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
                $this->assertEquals($this->assertions[$index][$function], $return, $function.", ".$index.\var_export($_SERVER, true)); 
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

}