<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class ServerRequest
 * @package FastD\Http
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    public array $attributes = [];

    /**
     * @var array
     */
    public array $cookieParams = [];

    /**
     * @var array
     */
    public array $queryParams = [];

    /**
     * @var array|object|null
     */
    public array|object|null $bodyParams = [];

    /**
     * @var array
     */
    public array $serverParams = [];

    /**
     * @var \Psr\Http\Message\UploadedFileInterface[]
     */
    public array $uploadFile = [];

    /**
     * ServerRequest constructor.
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param \Psr\Http\Message\StreamInterface|null $body
     * @param array $serverParams
     */
    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        ?StreamInterface $body = null,
        array $serverParams = []
    ) {
        parent::__construct($method, $uri, $headers, $body);

        $this
            ->withQueryParams($this->uri->getQuery())
            ->withServerParams($serverParams)
            ->withParsedBody($_POST)
            ->withCookieParams($_COOKIE)
            ->withUploadedFiles($_FILES);

        if (in_array(strtoupper($method), ['PUT', 'DELETE', 'PATCH', 'OPTIONS'])) {
            parse_str((string)$body, $data);

            if (empty($data)) {
                $data = json_decode((string)$body);
            }

            $this->withParsedBody($data);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @param array $server
     * @return $this
     */
    public function withServerParams(array $server): static
    {
        if (empty($this->header)) {
            array_walk($server, function ($value, $key) {
                if (str_starts_with($key, 'HTTP_')) {
                    $this->withAddedHeader(str_replace('HTTP_', '', $key), $value);
                }
            });
        }

        $this->serverParams = $server;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = false): mixed
    {
        return $this->cookieParams[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function withCookieParams(array $cookies): static
    {
        foreach ($cookies as $name => $value) {
            $this->cookieParams[$name] = $value;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, mixed $default = false): mixed
    {
        if (isset($this->queryParams[$key])) {
            return $this->queryParams[$key];
        }

        if (isset($this->bodyParams[$key])) {
            return $this->bodyParams[$key];
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function withQueryParams(array $query): static
    {
        foreach ($query as $name => $value) {
            $this->queryParams[$name] = $value;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadFile;
    }

    /**
     * {@inheritDoc}
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $this->uploadFile = static::normalizer($uploadedFiles);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParsedBody(): array|object|null
    {
        return $this->bodyParams;
    }

    /**
     * {@inheritDoc}
     */
    public function withParsedBody($data): static
    {
        $this->bodyParams = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute(string $name, mixed $default = null)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function withAttribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutAttribute(string $name): static
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }

        unset($this->attributes[$name]);

        return $this;
    }

    /**
     * @return string
     */
    public function getClientIP(): string
    {
        $unknown = 'unknown';
        $ip = 'unknown';

        if (
            isset($this->serverParams['HTTP_X_FORWARDED_FOR'])
            && $this->serverParams['HTTP_X_FORWARDED_FOR']
            && strcasecmp($this->serverParams['HTTP_X_FORWARDED_FOR'], $unknown)
        ) {
            $ip = $this->serverParams['HTTP_X_FORWARDED_FOR'];
        } elseif (
            isset($this->serverParams['REMOTE_ADDR'])
            && $this->serverParams['REMOTE_ADDR']
            && strcasecmp($this->serverParams['REMOTE_ADDR'], $unknown)
        ) {
            $ip = $this->serverParams['REMOTE_ADDR'];
        }

        if (str_contains($ip, ',')) {
            $ip = explode(',', $ip);
            $ip = reset($ip);
        }

        return $ip;
    }

    /**
     * @param array $files
     * @return array
     */
    public static function normalizer(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if (count($value) == count($value, COUNT_RECURSIVE)) {
                if ($value instanceof UploadedFileInterface) {
                    $normalized[$key] = $value;
                } elseif (isset($value['name'])) {
                    $normalized[$key] = UploadedFile::normalizer($value);
                } else {
                    throw new InvalidArgumentException('Invalid value in files specification');
                }
            } else {
                $array = [];

                foreach ($value['name'] as $index => $item) {
                    if (empty($item)) {
                        continue;
                    }

                    $array[] = UploadedFile::normalizer([
                        'name' => $value['name'][$index],
                        'type' => $value['type'][$index],
                        'tmp_name' => $value['tmp_name'][$index],
                        'error' => $value['error'][$index],
                        'size' => $value['size'][$index],
                    ]);
                }

                $normalized[$key] = $array;
            }
        }

        return $normalized;
    }

    /**
     * @param array $serverParams
     * @return string
     */
    public static function createUriFromGlobal(array $serverParams): string
    {
        $uri = 'http://';

        if (isset($serverParams['REQUEST_SCHEME'])) {
            $uri = strtolower($serverParams['REQUEST_SCHEME']).'://';
        } else {
            if (isset($serverParams['HTTPS']) && 'on' === $serverParams['HTTPS']) {
                $uri = 'https://';
            }
        }

        if (isset($serverParams['HTTP_HOST'])) {
            $uri .= $serverParams['HTTP_HOST'];
        } elseif (isset($serverParams['SERVER_NAME'])) {
            $uri .= $serverParams['SERVER_NAME'];
        }

        if (isset($serverParams['SERVER_PORT']) && !empty($serverParams['SERVER_PORT'])) {
            if (!in_array($serverParams['SERVER_PORT'], [80, 443])) {
                $uri .= ':'.$serverParams['SERVER_PORT'];
            }
        }

        if (isset($serverParams['REQUEST_URI'])) {
            $requestUriParts = explode('?', $serverParams['REQUEST_URI']);
            $uri .= $requestUriParts[0];
            unset($requestUriParts);
        }

        if (isset($serverParams['QUERY_STRING']) && !empty($serverParams['QUERY_STRING'])) {
            $uri .= '?'.$serverParams['QUERY_STRING'];
        }

        return $uri;
    }

    /**
     * Create a new server request from PHP globals.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        foreach ($headers as $name => $value) {
            unset($headers[$name]);
            $name = str_replace('-', '_', $name);
            $headers[$name] = $value;
        }

        return new static($method, static::createUriFromGlobal($_SERVER), $headers, new PhpInputStream(), $_SERVER);
    }
}
