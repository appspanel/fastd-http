<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Class Stream
 *
 * @package FastD\Http
 */
class Stream implements StreamInterface
{
    /**
     * @var string
     */
    protected string $stream;

    /**
     * @var string
     */
    protected string $mode;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var bool
     */
    protected bool $readable = false;

    /**
     * @var bool
     */
    protected bool $writable = false;

    /**
     * @var bool
     */
    protected bool $seekable = false;

    /**
     * @var array<string,array<string,bool>>
     */
    protected static array $modeHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];

    /**
     * Stream constructor.
     *
     * @param string$stream
     * @param string $mode
     * @see http://php.net/manual/zh/wrappers.php.php
     */
    public function __construct(string $stream, string $mode = 'r')
    {
        $this->stream = $stream;

        $this->mode = $mode;

        $this->resource = fopen($stream, $this->mode);

        $meta = $this->getMetadata();

        $this->seekable = $meta['seekable'];
        $this->readable = isset(static::$modeHash['read'][$meta['mode']]);
        $this->writable = isset(static::$modeHash['write'][$meta['mode']]);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        try {
            $this->rewind();

            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if (null !== $this->resource) {
            $resource = $this->detach();
            fclose($resource);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        if (!$this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'];
    }

    /**
     * {@inheritDoc}
     */
    public function tell(): int
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot tell position.');
        }

        $result = ftell($this->resource);

        if (!is_int($result)) {
            throw new RuntimeException('Error occurred during tell operation.');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function eof(): bool
    {
        if (!$this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * {@inheritDoc}
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot seek position.');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            throw new RuntimeException('Error seeking within stream.');
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): bool
    {
        return $this->seek(0);
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     *
     *  {@inheritDoc}
     */
    public function write(string $string): int
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot write.');
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            throw new RuntimeException('Unable to writing from stream.');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * {@inheritDoc}
     */
    public function read(int $length): string
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot read');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $string = fread($this->resource, $length);

        if (false === $string) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $result = stream_get_contents($this->resource);

        if (false === $result) {
            throw new RuntimeException('Error reading from stream');
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(?string $key = null): mixed
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot write');
        }

        $metadata = stream_get_meta_data($this->resource);

        if (null === $key) {
            return $metadata;
        }

        return !array_key_exists($key, $metadata) ? null : $metadata[$key];
    }
}
