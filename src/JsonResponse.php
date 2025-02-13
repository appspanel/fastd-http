<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2018
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace FastD\Http;

/**
 * Class JsonResponse
 *
 * @package FastD\Http
 */
class JsonResponse extends Response
{
    const JSON_OPTIONS = JSON_UNESCAPED_UNICODE;

    /**
     * Constructor.
     *
     * @param mixed $data    The response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     */
    public function __construct(mixed $data, int $status = Response::HTTP_OK, array $headers = [])
    {
        $json = json_encode($data, static::JSON_OPTIONS);

        $this->withContentType('application/json; charset=UTF-8');

        parent::__construct($json, $status, $headers);
    }

    /**
     * @return mixed
     */
    public function toArray(): mixed
    {
        return json_decode($this->getContents(), true);
    }
}
