<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

use CurlHandle;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Http Request Client
 *
 * Class Request
 *
 * @package FastD\Http
 */
class Request extends Message implements RequestInterface
{
    const USER_AGENT = 'PHP Curl/1.1 (+https://github.com/JanHuang/http)';

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @var string
     */
    protected string $method = 'GET';

    /**
     * @var string
     */
    protected string $requestTarget = '/';

    /**
     * @var \Psr\Http\Message\UriInterface
     */
    protected UriInterface $uri;

    /**
     * Supported HTTP methods
     *
     * @var array
     */
    private array $validMethods = [
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
    ];

    /**
     * Request constructor.
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param \Psr\Http\Message\StreamInterface|null $body
     */
    public function __construct(string $method, string $uri, array $headers = [], ?StreamInterface $body = null)
    {
        $this->withMethod($method);
        $this->withUri(new Uri($uri));
        $this->withHeaders($headers);

        parent::__construct($body);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    /**
     * {@inheritDoc}
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $this->uri->withPath($requestTarget);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     */
    public function withMethod(string $method): static
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->validMethods, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        $this->method = $method;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * {@inheritDoc}
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function withOption(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function withOptions(array $options): static
    {
        $this->options = $options + $this->options;

        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withBasicAuthentication(string $username, string $password): static
    {
        $this->withOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->withOption(CURLOPT_USERPWD, $username . ':' . $password);

        return $this;
    }

    /**
     * @param mixed $referer
     * @return $this
     */
    public function withReferrer(mixed $referer): static
    {
        $this->withOption(CURLOPT_REFERER, $referer);

        return $this;
    }

    /**
     * @param array|string $data
     * @param array $headers
     * @return \FastD\Http\Response
     */
    public function send(mixed $data = [], array $headers = []): Response
    {
        $ch = curl_init();
        $url = (string)$this->uri;

        is_array($data) && $data = http_build_query($data);

        // DELETE request may has body
        if (in_array($this->getMethod(), ['PUT', 'POST', 'DELETE', 'PATCH'])) {
            $this->withOption(CURLOPT_POSTFIELDS, $data);
        } elseif (!empty($data)) {
            $url .= (!str_contains($url, '?') ? '?' : '&').$data;
        }

        if (!array_key_exists(CURLOPT_USERAGENT, $this->options)) {
            $this->withOption(CURLOPT_USERAGENT, static::USER_AGENT);
        }

        // forces only empty Expect
        // see: https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Headers/Expect
        // see: https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Status/100
        $encoding = '';

        foreach ($headers as $index => $header) {
            if (str_starts_with(strtolower($header), 'expect:')) {
                unset($headers[$index]);
            }
            elseif (str_starts_with(strtolower($header), 'accept-encoding:')) {
                $encoding = trim(substr($header, 16));
                unset($headers[$index]);
            }
        }
        $headers[] = 'Expect:';

        $this->withOption(CURLOPT_ENCODING, $encoding);
        $this->withOption(CURLOPT_HTTPHEADER, $headers);
        $this->withOption(CURLOPT_URL, $url);
        $this->withOption(CURLOPT_CUSTOMREQUEST, $this->getMethod());
        $this->withOption(CURLINFO_HEADER_OUT, false);
        $this->withOption(CURLOPT_HEADER, false);
        $this->withOption(CURLOPT_RETURNTRANSFER, true);
        $this->withOption(CURLOPT_HEADERFUNCTION, function(CurlHandle $curl, string $header) use(&$responseHeaders): int
        {
            $length = strlen($header);
            $header = explode(':', $header, 2);

            if (count($header) < 2) {
                return $length;
            }

            $name = strtolower(trim($header[0]));

            if (!isset($responseHeaders[$name])) {
                $responseHeaders[$name] = [];
            }

            $responseHeaders[$name][] = trim($header[1]);

            return $length;
        });

        foreach ($this->options as $key => $option) {
            curl_setopt($ch, $key, $option);
        }

        $response = curl_exec($ch);
        $errorCode = curl_errno($ch);
        $errorMsg = curl_error($ch);

        if ($errorCode !== CURLE_OK) {
            throw new HttpException($errorMsg);
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        unset($ch);

        $responseHeaders = array_map(
            static fn(array $values): string => implode(',', $values),
            $responseHeaders
        );

        if (isset($headers['Content-Encoding'])) {
            $response = zlib_decode($response);
        }

        return new Response($response, $statusCode, $responseHeaders);
    }
}
