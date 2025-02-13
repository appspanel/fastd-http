<?php
use FastD\Http\Request;
use FastD\Http\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */
class RequestTest extends TestCase
{
    public function testRequestUri()
    {
        $request = new Request('GET', 'http://example.com');

        $this->assertEquals('example.com', $request->getUri()->getHost());
        $this->assertEquals(80, $request->getUri()->getPort());
        $this->assertEquals('/', $request->getUri()->getPath());
        $this->assertEquals($request->getRequestTarget(), $request->getUri()->getPath());
    }

    public function testInvalidRequestUri()
    {
        $this->expectException(InvalidArgumentException::class);

        new Request('GET', '///');
    }

    public function testRequestMethod()
    {
        $this->expectException(InvalidArgumentException::class);

        $request = new Request('GET', 'http://example.com');
        $this->assertEquals('GET', $request->getMethod());
        // Test invalid method
        $request->withMethod('ABC');
    }

    public function server()
    {
        $uri = new Uri('https://postman-echo.com/get');

        return new Request('GET', (string) $uri);
    }

    public function testRequestTarget()
    {
        $request = $this->server();
        $response = $request->send();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRequestWithLargeBody()
    {
        $request = new Request('POST', 'https://github.com/session');

        $response = $request->send([
            'a' => str_repeat('11111', 1000),
        ]);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('GitHub.com', $response->getHeader('server')[0]);
    }

    public function testResponseWithEncoding()
    {
        $request = $this->server();

        $response = $request->send('', ['Accept-Encoding: gzip']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContents());

        $response = $request->send('', ['Accept-Encoding: deflate']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContents());

        $response = $request->send('', ['Accept-Encoding: gzip, deflate']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContents());
    }

    public function testPostRawRequest()
    {
        $raw = '<xml><appid><![CDATA[123456789123456789]]></appid><mch_id>1234567890</mch_id><nonce_str><![CDATA[589d897212f9c]]></nonce_str><body><![CDATA[123]]></body><out_trade_no><![CDATA[runnerlee_001]]></out_trade_no><fee_type><![CDATA[CNY]]></fee_type><total_fee>1</total_fee><spbill_create_ip><![CDATA[127.0.0.1]]></spbill_create_ip><trade_type><![CDATA[NATIVE]]></trade_type><notify_url><![CDATA[http://github.com]]></notify_url><detail><![CDATA[runnerlee_test_payment]]></detail><sign><![CDATA[ZXCVBNMASDFGHJKLQWERTYUIOP123456]]></sign></xml>';
        $request = new Request('POST', 'https://httpbin.org/post');
        $responseBody = $request->send($raw, ['Content-Type: application/xml'])->getContents();
        $responseBody = json_decode($responseBody, true);

        $this->assertEquals($raw, $responseBody['data']);
    }

    public function testWithOptions()
    {
        $request = new Request('GET', '/');
        $request->withOptions([
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_USERAGENT => 'Hello World',
        ]);
        $this->assertEquals(
            [
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_USERAGENT => 'Hello World',
            ],
            $request->getOptions()
        );
        $request->withOptions([
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        $this->assertEquals(
            [
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_USERAGENT => 'Hello World',
            ],
            $request->getOptions()
        );
    }
}
