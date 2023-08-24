<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Psr7 Http Message
 *
 * Class Message
 *
 * @package FastD\Http
 */
class Message implements MessageInterface
{
    /**
     * @var array
     */
    public array $header = [];

    /**
     * @var string
     */
    protected string $protocolVersion = '1.1';

    /**
     * @var \Psr\Http\Message\StreamInterface
     */
    protected StreamInterface $stream;

    /**
     * Message constructor.
     *
     * @param \Psr\Http\Message\StreamInterface|null $stream
     */
    public function __construct(StreamInterface $stream = null)
    {
        if (null === $stream) {
            $stream = new Stream('php://memory', 'wb+');
        }

        $this->withBody($stream);
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function withProtocolVersion(string $version): MessageInterface
    {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders(): array
    {
        return $this->header;
    }

    /**
     * {@inheritDoc}
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->header[strtolower($name)]);
    }

    /**
     * {@inheritDoc}
     * @return array|bool PSR isn't respected.
     */
    public function getHeader(string $name): array|false
    {
        return $this->hasHeader($name) ? $this->header[strtolower($name)] : false;
    }

    /**
     * {@inheritDoc}
     * @return string|null PSR isn't respected.
     */
    public function getHeaderLine(string $name): ?string
    {
        $value = $this->getHeader($name);

        if (empty($value)) {
            return null;
        }

        return implode(',', $value);
    }

    /**
     * {@inheritDoc}
     * @param string $value PSR isn't respected.
     */
    public function withHeader(string $name, $value): static
    {
        $this->header[strtolower($name)] = [$value];

        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $key => $header) {
            if (is_array($header)) {
                foreach ($header as $item) {
                    $this->withAddedHeader($key, $item);
                }
            } else {
                $this->withHeader($key, $header);
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     * @param string $value PSR isn't respected.
     */
    public function withAddedHeader(string $name, $value): static
    {
        $this->header[strtolower($name)][] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutHeader(string $name): static
    {
        $name = strtolower($name);

        if (!$this->hasHeader($name)) {
            return $this;
        }

        unset($this->header[$name]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * {@inheritDoc}
     */
    public function withBody(StreamInterface $body): static
    {
        $this->stream = $body;

        return $this;
    }
}
