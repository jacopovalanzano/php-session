<?php

namespace Tundra\Session;

use RuntimeException;
use SessionHandlerInterface;

/**
 * Class SessionDriverWrapper
 *
 * @author Jacopo Valanzano
 * @package Tundra\Session
 * @license MIT
 */
class SessionDriverWrapper implements \SessionHandlerInterface
{

    use SessionTrait;

    /**
     * @var SessionHandlerInterface
     */
    protected $sessionDriver;

    /**
     * SessionDriverWrapper constructor.
     *
     * @param SessionHandlerInterface $sessionDriver
     */
    public function __construct(SessionHandlerInterface $sessionDriver)
    {
        $this->sessionDriver = $sessionDriver;
    }

    /**
     * Equivalent of "session_write_close".
     *
     * @inheritDoc
     */
    public function close()
    {
        return $this->sessionDriver->close();
    }

    /**
     * @inheritDoc
     */
    public function destroy($sessionId)
    {
        return $this->sessionDriver->destroy($sessionId);
    }

    /**
     * @inheritDoc
     */
    public function gc($maxLifetime)
    {
        return $this->sessionDriver->gc($maxLifetime);
    }

    /**
     * @inheritDoc
     */
    public function open($path, $sessionName = null)
    {
        return $this->sessionDriver->open($path, $sessionName);
    }

    /**
     * @inheritDoc
     */
    public function read($sessionId)
    {
        return $this->sessionDriver->read($sessionId);
    }

    /**
     * @inheritDoc
     */
    public function write($sessionId, $data)
    {
        return $this->sessionDriver->write($sessionId, $data);
    }

    /**
     * @return SessionHandlerInterface
     */
    public function getDriver()
    {
        return $this->sessionDriver;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws RuntimeException
     */
    public function __call($method, $args)
    {
        if(\method_exists($this->sessionDriver, $method)) {
            return $this->sessionDriver->{$method}(...$args);
        }

        throw new RuntimeException(\get_called_class()." does not have a method '$method'");
    }
}
