<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

use CURLFile;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Class File
 *
 * @package FastD\Http\Bag
 */
class UploadedFile extends CURLFile implements UploadedFileInterface
{
    /**
     * @var string
     */
    protected string $tmpName;

    /**
     * @var int
     */
    protected int $error;

    /**
     * @var int
     */
    protected int $size;

    /**
     * @var false
     */
    protected bool $moved = false;

    /**
     * @var \Psr\Http\Message\StreamInterface|null
     */
    protected ?StreamInterface $stream = null;

    /**
     * File constructor.
     *
     * @param string|null $name
     * @param string|null $type
     * @param string $tmpName
     * @param int $error
     * @param int $size
     */
    public function __construct(?string $name, ?string $type, string $tmpName, int $error, int $size)
    {
        $this->tmpName = $tmpName;
        $this->error = $error;
        $this->size = $size;

        parent::__construct($tmpName, $type, $name);
    }

    /**
     * {@inheritDoc}
     */
    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->tmpName);

        return $this->stream;
    }

    /**
     * {@inheritDoc}
     */
    public function moveTo(string $targetPath): string
    {
        $targetFile = $targetPath
            . DIRECTORY_SEPARATOR
            . hash_file('md5', $this->tmpName)
            . '.'
            . pathinfo($this->postname, PATHINFO_EXTENSION);


        if ('cli' === PHP_SAPI) {
            if (!rename($this->tmpName, $targetFile)) {
                throw new RuntimeException('Failed to move uploaded file.');
            }

            $this->moved = true;

            return $targetFile;
        }

        if (!is_uploaded_file($this->tmpName)) {
            throw new InvalidArgumentException('Upload file is invalid.');
        }

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        if (!move_uploaded_file($this->tmpName, $targetFile)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        $this->moved = true;

        return $targetFile;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->mime;
    }

    /**
     * @param array $file
     * @return \FastD\Http\UploadedFile
     */
    public static function normalizer(array $file): UploadedFile
    {
        return new UploadedFile(
            $file['name'],
            $file['type'],
            $file['tmp_name'],
            (int) $file['error'],
            (int) $file['size']
        );
    }
}
