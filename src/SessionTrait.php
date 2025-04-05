<?php

namespace Tundra\Session;
/**
 * Trait Session
 *
 * A default session template.
 *
 * @author Jacopo Valanzano
 * @package Session
 * @license MIT
 */
trait SessionTrait
{

    /**
     * Sets a value with the associated key in the session storage.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Returns the session data associated with the given key.
     *
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function get($key)
    {

        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        throw new \InvalidArgumentException(\get_called_class() . " tried to get the \$_SESSION['$key'] parameter. Try isset first.");
    }

    /**
     * Adds data to an existing session key.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function add($key, $value)
    {
        $_SESSION[$key][] = $value;
    }

    /**
     * Checks if the given (string) key exists among the session globals.
     *
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return (isset($_SESSION[$key]) === true);
    }

    /**
     * Removes a session parameter from the session globals.
     *
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Retrieves the session id.
     *
     * @return false|string
     */
    public function getId()
    {
        return \session_id();
    }

    /**
     * Sets/replaces the session id.
     *
     * @param string $id
     * @return false|string
     */
    public function setId(string $id)
    {
        return \session_id($id);
    }

    /**
     * @param string|null $id
     * @return false|string
     */
    public function sessionId(string $id = null)
    {
        return \session_id($id);
    }

    /**
     * Retrieves the session name.
     *
     * @return false|string
     */
    public function getName()
    {
        return \session_name();
    }

    /**
     * Sets the session name. Must be called before starting the session.
     *
     * @param string $name
     * @return false|string
     */
    public function setName(string $name = "__TUNDRA_PHPSESSID")
    {
        return \session_name($name);
    }

    /**
     * @param string|null $sessionName
     * @return false|string
     */
    public function sessionName($sessionName)
    {
        return \session_name($sessionName);
    }

    /**
     * Starts a (new) session.
     *
     * @param array $options
     * @return bool
     */
    public function sessionStart(array $options = []): bool
    {
        return \session_start($options);
    }

    /**
     * Destroys all the session data (including from storage, and the session id)
     *
     * @return bool
     */
    public function sessionDestroy(): bool
    {
        return \session_destroy();
    }

    /**
     * @return bool|void
     */
    public function sessionWriteClose()
    {
        return \session_write_close();
    }

    /**
     * Closes the session and writes the session data to storage.
     *
     * @return bool|void
     */
    public function sessionClose()
    {
        return \session_write_close();
    }

    /**
     * Generates a new id for the session.
     *
     * @param bool $delete_old_session
     * @return bool
     */
    public function sessionRegenerateId(bool $delete_old_session = false): bool
    {
        return \session_regenerate_id($delete_old_session); // "true" would delete the old session data
    }

    /**
     * @return bool|void
     */
    public function sessionReset()
    {
        return \session_reset();
    }

    /**
     * Frees all the session globals (variables).
     *
     * @return bool|void|null
     */
    public function sessionUnset()
    {
        $_SESSION = array();

        return \session_unset();
    }

    /**
     * Closes the session without writing to storage, but maintaining the data in the session global.
     *
     * @return bool|void
     */
    public function sessionAbort(): bool
    {
        return \session_abort();
    }

    /**
     * Returns the current session status.
     *
     * @return int
     */
    public function sessionStatus(): int
    {
        return \session_status();
    }

    /**
     * Checks if the session was started.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->sessionStatus() === \PHP_SESSION_ACTIVE;
    }

    /**
     * @param int $value
     * @return false|int
     */
    public function sessionCacheExpire($value)
    {
        return \session_cache_expire($value);
    }

    /**
     * @param string $value
     * @return false|string
     */
    public function sessionCacheLimiter($value)
    {
        return \session_cache_limiter($value);
    }
}
