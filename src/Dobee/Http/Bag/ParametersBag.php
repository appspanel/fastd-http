<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 15/2/23
 * Time: 上午1:19
 * Github: https://www.github.com/janhuang 
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 */

namespace Dobee\Http\Bag;

/**
 * Class ParametersBag
 *
 * @package Dobee\Http\Bag
 */
class ParametersBag implements BagInterface
{
    /**
     * @var array|null
     */
    protected $parameters;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        foreach ($parameters as $name => $value) {
            $this->parameters[strtoupper($name)] = $value;
        }
    }

    /**
     * @param $key
     * @return $this
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->parameters[$key]);
        }

        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->parameters[$key]);
    }

    /**
     * @param string $name
     * @param       $value
     * @return $this
     */
    public function set($name, $value)
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * Filter request parameters.
     *
     * @param        $key
     * @param string|\Closure $validate
     * @return string|bool
     */
    public function get($key, $validate = Filter::STRING)
    {
        if (!$this->has($key)) {
            return false;
        }

        if (is_callable($validate)) {
            return $validate($this->parameters[$key]);
        }

        return call_user_func_array('\Dobee\Http\Bag\Filter::' . $validate, array($this->parameters[$key]));
    }

    /**
     * @return array|null
     */
    public function all()
    {
        return $this->parameters;
    }
}