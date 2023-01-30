<?php
use FastD\Http\PhpInputStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 *
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */
class PhpInputStreamTest extends TestCase
{
    /**
     * @var \Psr\Http\Message\StreamInterface
     */
    protected StreamInterface $stream;

    public function setUp(): void
    {
        $this->stream = new PhpInputStream('php://temp', 'wr');

        $this->stream->write(http_build_query([
            'age' => 11
        ]));
    }

    public function testPhpInputRawData()
    {
        $this->stream->rewind();
        $content = $this->stream->getContents();

        parse_str($content, $_POST);
        $this->assertEquals(['age' => 11], $_POST);
    }
}
