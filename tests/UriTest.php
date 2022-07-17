<?php declare(strict_types=1);

namespace Dominicus75\Psr7\Tests;

use Dominicus75\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    private array $string_input = [
        0 => [
            'uri'    => 'https://john.doe:pa$$w0rD@127.0.1.1:443/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
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
            'uri'    => 'http://127.0.1.1:80/directory/subdirectory/file.html?query0=izé&query1=bigyó#töredék',
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
        ]
    ];

    private array $assertions = [
        0 => [
            'toString'     => 'https://john.doe:pa$$w0rD@127.0.1.1:443/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
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
            'toString'     => 'http://127.0.1.1:80/directory/subdirectory/file.html?query0=iz%C3%A9&query1=bigy%C3%B3#t%C3%B6red%C3%A9k',
            'getScheme'    => 'https',
            'getUserInfo'  => '',
            'getAuthority' => '',
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
            'getAuthority' => '',
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
            'getAuthority' => '',
            'getHost'      => 'en.localhost.info',
            'getPort'      => 456,
            'getPath'      => '/directory/subdirectory/file.html',
            'getQuery'     => 'query0=iz%C3%A9&query1=bigy%C3%B3',
            'getFragment'  => 't%C3%B6red%C3%A9k'
        ]
    ];

    public function testCreateUriFromGivenString()
    {
        foreach ($this->string_input as $index => $testcase) {
            $uri = new Uri($this->string_input[$index]['uri']);
            $actual = [
                'toString'     => $uri,
                'getScheme'    => $uri->getScheme(),
                'getUserInfo'  => $uri->getUserInfo(),
                'getAuthority' => $uri->getAuthority(),
                'getHost'      => $uri->getHost(),
                'getPort'      => $uri->getPort(),
                'getPath'      => $uri->getPath(),
                'getQuery'     => $uri->getQuery(),
                'getFragment'  => $uri->getFragment()    
            ];
            foreach ($actual as $function => $return) { $this->assertEquals($this->assertions[$function], $return); }
        }
    }

    /*public function testCreateUriFromSuperglobals()
    {
        foreach ($this->string_input as $index => $testcase) {
            $_SERVER['REQUEST_SCHEME'] = $this->string_input[$index]['scheme'];
            $_SERVER['PHP_AUTH_USER']  = $this->string_input[$index]['user'];
            $_SERVER['PHP_AUTH_PW']    = $this->string_input[$index]['pass'];
            $_SERVER['HTTP_HOST']      = $this->assertions[$index]['getHost'];
            $_SERVER['SERVER_PORT']    = $this->string_input[$index]['port'];
            $_SERVER['REQUEST_URI']    = $this->assertions[$index]['getPath'];
            $_SERVER['REQUEST_URI']   .= '?'.$this->assertions[$index]['getQuery'];

        }
    }*/


}