<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

use FastD\Http\SwooleServerRequest;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class SwooleServerRequestTest extends TestCase
{
    public function dataFromSwoole()
    {
        $swoole = new Request();

        // https://httpbin.org/get
        $swoole->get = [];
        $swoole->post = [];
        $swoole->files = [];
        $swoole->header = [
            'host' => 'httpbin.org',
            'pragma' => 'no-cache',
            'cache-control' => 'no-cache',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36',
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'fr-FR,fr;q=0.8,en-US;q=0.5,en;q=0.3',
        ];
        $swoole->server = [
            'request_method' => 'GET',
            'request_uri' => '/get',
            'path_info' => '/get',
            'request_time' => '1483065025',
            'request_time_float' => '1483065025.0912',
            'server_port' => '80',
            'remote_port' => '49856',
            'remote_addr' => '11.11.11.1',
            'server_protocol' => 'HTTP/1.1',
            'server_software' => 'swoole-http-server'
        ];
        $swoole->cookie = [];

        return $swoole;
    }

    // @todo How can Swoole requests be tested?
    public function testSwooleServerRequestCreateFromSwoole()
    {
        $swoole = $this->dataFromSwoole();
        $swoole->fd = 0;
        $serverRequest = SwooleServerRequest::createServerRequestFromSwoole($swoole);
        $this->assertEmpty($serverRequest->getQueryParams());
        $this->assertEmpty($serverRequest->getParsedBody());
        $this->assertEmpty($serverRequest->getUploadedFiles());
    }
}
