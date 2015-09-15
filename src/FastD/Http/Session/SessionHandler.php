<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 15/9/2
 * Time: 下午4:07
 * Github: https://www.github.com/janhuang
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 * WebSite: http://www.janhuang.me
 */

namespace FastD\Http\Session;

use FastD\Http\Session\Storage\SessionStorageInterface;

class SessionHandler implements \SessionHandlerInterface
{
    /**
     * @var SessionStorageInterface
     */
    protected $storage;

    /**
     * @param SessionStorageInterface|null $sessionStorageInterface
     */
    public function __construct(SessionStorageInterface $sessionStorageInterface)
    {
        if (null !== $sessionStorageInterface) {
            $this->storage = $sessionStorageInterface;
        }
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->storage->getTtl();
    }

    /**
     * @param int $ttl
     * @return $this
     */
    public function setTtl($ttl)
    {
        return $this->storage->setTtl($ttl);
    }

    /**
     * @param SessionStorageInterface $sessionStorageInterface
     * @return $this
     */
    public function setStorage(SessionStorageInterface $sessionStorageInterface)
    {
        $this->storage = $sessionStorageInterface;

        return $this;
    }

    /**
     * @return SessionStorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return bool
     */
    public function close()
    {
        $this->storage = null;
        unset($this->storage);
    }

    /**
     * @param $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        return $this->storage->remove($session_id);
    }

    /**
     * @param $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * @param $save_path
     * @param $session_id
     * @return bool
     */
    public function open($save_path, $session_id)
    {
        $_SESSION = $this->storage->get('*');
        return true;
    }

    /**
     * Return session formatter string.
     *
     * @param $session_id
     * @return string
     */
    public function read($session_id)
    {
        return $this->storage->get($session_id);
    }

    /**
     * @param $session_id
     * @param $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        if (null === $session_data || empty($session_data)) {
            return $this->storage->remove($session_id);
        }

        return $this->storage->set($session_id, $session_data);
    }
}