<?php

namespace Tundra\Session;

use \BadMethodCallException;
use \RuntimeException;

/**
 * Class SessionHandler
 *
 * Handles the current session and provides overloading.
 * This class can be extended by other session handlers, for example a UserSession class.
 *
 * @author Jacopo Valanzano
 * @package Tundra\Session
 * @license MIT
 */
class SessionHandler extends SessionManager
{

    use SessionTrait;

    /**
     * Erase the current session.
     *
     * @return void|bool
     */
    public function erase($sessionId = null)
    {
        $this->invalidate(true)->destroy($sessionId ?: self::$sessionId);
    }

    /**
     * Clears all session attributes and generates a new session id.
     *
     * @param bool $deleteOldSession
     * @return self
     */
    public function invalidate(bool $deleteOldSession = false)
    {
        return $this->clear()->regenerate($deleteOldSession);
    }

    /**
     *  Generates a new session id.
     *
     * @param bool $deleteOldSession
     * @return self
     */
    public function regenerate(bool $deleteOldSession = false)
    {
        $this->driver()->sessionRegenerateId($deleteOldSession);

        return $this;
    }

    /**
     * Clears all session attributes. Should be executed before the destroying a session.
     *
     * @return self
     */
    public function clear()
    {
        $this->driver()->sessionUnset();

        return $this;
    }

    /**
     * @return self
     */
    public function unset()
    {
        $this->driver()->sessionUnset();

        return $this;
    }

    /**
     * @return void
     */
    public function save() {
        $data = session_encode();

        foreach ($this->driver as $sessionDriver) {
            $sessionDriver->write($this->getId(), $data);
        }
    }

    /**
     * Starts a session.
     *
     * @param array $options
     * @return bool
     */
    public function sessionStart(array $options = []): bool
    {
        return $this->start($options);
    }

    /**
     * Destroys the current session.
     *
     * @param string $sessionId
     * @return bool
     */
    public function sessionDestroy($sessionId): bool
    {
        return $this->destroy($sessionId);
    }

    /**
     * Regenerates the session id. This process implies the old session is erased.
     *
     * @param bool $delete_old_session
     * @return SessionHandler
     */
    public function sessionRegenerateId(bool $delete_old_session = false): SessionHandler
    {
        return $this->regenerate($delete_old_session); // "true" deletes the old session parameters
    }

    /**
     * {@inheritdoc }
     */
    public function getId()
    {
        return parent::getId();
    }

    /**
     * {@inheritdoc }
     */
    public function setId($sessionId = null)
    {
        return parent::setId($sessionId);
    }

    /**
     * {@inheritdoc }
     */
    public function getName()
    {
        return parent::getName();
    }

    /**
     * @param $name
     * @return false|mixed|string
     */
    public function setName($name)
    {
        return parent::setName($name);
    }

    /**
     * Stores data to session.
     *
     * @param string $name
     * @param mixed $value
     * @throws BadMethodCallException
     */
    public function __set($name, $value)
    {
        $this->driver()->set($name, $value);
    }

    /**
     * Retrieves data from session.
     *
     * @param $name
     * @return mixed
     * @throws BadMethodCallException|\InvalidArgumentException
     */
    public function __get($name)
    {
        return $this->driver()->get($name);
    }

    /**
     * @param string $name
     * @return bool
     * @throws BadMethodCallException
     */
    public function __isset($name): bool
    {
        return $this->driver()->has($name);
    }

    /**
     * @param string $name
     * @return void
     * @throws BadMethodCallException
     */
    public function __unset($name)
    {
        $this->driver()->forget($name);
    }

    /**
     * @param mixed $method
     * @param mixed $parameters
     * @throws RuntimeException
     */
    public function __call($method, $parameters)
    {
        if(\method_exists($this, $method)) {
            return \call_user_func_array([$this, $method], $parameters);
        }

        throw new RuntimeException(\get_called_class()." does not have a method '$method'");
    }
}
