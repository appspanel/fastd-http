<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 15/6/12
 * Time: 下午3:58
 * Github: https://www.github.com/janhuang 
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 */

namespace FastD\Protocol\Http\Attribute;

use FastD\Protocol\Attribute\Attribute;

/**
 * Class ServerAttribute
 *
 * @package FastD\Protocol\Http\Attribute
 */
class ServerAttribute extends Attribute
{
    protected $pathInfo;

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->has('REQUEST_FORMAT') ? $this->get('REQUEST_FORMAT') : $this->prepareFormat();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        $headers = array();

        foreach ($this->all() as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
        }

        return $headers;
    }

    /**
     * @return string
     */
    public function getPathInfo()
    {
        if ($this->pathInfo) {
            return $this->pathInfo;
        }

        $pathInfo = $this->has('PATH_INFO') ? $this->get('PATH_INFO') : $this->preparePathInfo();

        if ('' != pathinfo($pathInfo, PATHINFO_EXTENSION)) {
            $pathInfo = substr($pathInfo, 0, strpos($pathInfo, '.'));
        }

        $this->pathInfo = $pathInfo;
        unset($pathInfo);

        return $this->pathInfo;
    }

    /**
     * @return string
     */
    public function getRequestUri()
    {
        return $this->has('REQUEST_URI') ? $this->get('REQUEST_URI') : $this->prepareRequestUri();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->get('SCRIPT_NAME');
    }

    /**
     * @return bool|string
     */
    public function getRequestTime()
    {
        if (!$this->has('REQUEST_TIME_FLOAT')) {
            $this->set('REQUEST_TIME_FLOAT', microtime(true));
        }

        return $this->get('REQUEST_TIME_FLOAT');
    }

    /**
     * @return bool|string
     */
    protected function prepareRequestUri()
    {
        return $this->get('PHP_SELF');
    }

    /**
     * @return string
     */
    protected function preparePathInfo()
    {
        $baseUrl = $this->getBaseUrl();

        if (null === $baseUrl || null === ($requestUri = $this->getRequestUri()) || $baseUrl === $requestUri) {
            return '/';
        }

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if (false === ($pathInfo = substr($requestUri, strlen($baseUrl)))) {
            return '/';
        }

        return $pathInfo;
    }

    /**
     * @return string
     */
    protected function prepareFormat()
    {
        $pathInfo = $this->has('PATH_INFO') ? $this->get('PATH_INFO') : $this->preparePathInfo();

        $format = '' == ($format = pathinfo($pathInfo, PATHINFO_EXTENSION)) ? 'php' : $format;

        $this->set('REQUEST_FORMAT', $format);

        return $format;
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $value) {
            if ($this->has($value)) {
                return $this->get($value);
            }
        }

        return 'unknown';
    }
}