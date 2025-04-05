<?php


namespace Tundra\Session;

use \RuntimeException;

/**
 * Class AbstractSession
 *
 * @author Jacopo Valanzano
 * @package Tundra\Session
 * @license MIT
 */
abstract class AbstractSession implements \SessionHandlerInterface
{

    /**
     * The default session name (when not set).
     */
    const _SESSION_NAME = "__TUNDRA_PHPSESSID";

    /**
     * @var string
     */
    protected static $sessionId;

    /**
     * @var string
     */
    protected static $sessionName;

    /**
     * @var bool
     */
    private static $sessionNameSet = false;

    /**
     * Retrieves the driver instance.
     *
     * @param string|null $name
     * @return SessionDriverWrapper
     */
    abstract public function driver(string $name = null): SessionDriverWrapper;

    /**
     * Returns the current session id.
     *
     * @return false|string
     */
    abstract public function getId();

    /**
     * Sets the (new) session id.
     *
     * @param string $sessionId
     * @return false|string
     */
    abstract public function setId(string $sessionId);

    /**
     * Sets the session name.
     *
     * @param string $name
     * @return mixed
     */
    abstract public function setName(string $name);

    /**
     * @return false|string
     */
    abstract public function getName();

    /**
     * @param $savePath
     * @param $sessionName
     * @return bool
     */
    abstract public function open($savePath, $sessionName);

    /**
     * @return bool
     */
    abstract public function close();

    /**
     * @param $sessionId
     * @return false|string
     */
    abstract public function read($sessionId);

    /**
     * @param $sessionId
     * @param $data
     * @return bool
     */
    abstract public function write($sessionId, $data);

    /**
     * @param $sessionId
     * @return bool
     */
    abstract public function destroy($sessionId);

    /**
     * @param $maxLifetime
     * @return false|int
     */
    abstract public function gc($maxLifetime);

    /**
     * Set the default session name only once.
     */
    public function __construct()
    {
        if (! self::$sessionNameSet) {
            \session_name('__TUNDRA_PHPSESSID'); // Set only once
            self::$sessionNameSet = true;
        }
    }

    /**
     * Starts the session.
     *
     * @param array $options
     * @return bool
     * @throws \RuntimeException
     */
    public function start(array $options = []): bool
    {

        if ( \session_status() === 0 ) {
            throw new \RuntimeException("Session must be enabled", PHP_SESSION_DISABLED);
        }

        // Session enabled, but not started
        if ( \session_status() === 1 ) {

            \ini_set('session.save_handler', 'files');

            // Make sure use_strict_mode is enabled.
            // use_strict_mode is mandatory for security reasons.
            \ini_set('session.use_strict_mode', '1');

            // Instruct php to use Tundra session handler.
            if(! \session_set_save_handler (
                function ($path, $sessionName)  { return $this->driver()->open($path, $sessionName); },
                function ()                     { return $this->driver()->close(); },
                function ($sessionId)           { return $this->driver()->read($sessionId); },
                function ($sessionId, $data)    { return $this->driver()->write($sessionId, $data); },
                function ($sessionId)           { return $this->driver()->destroy($sessionId); },
                function ($maxLifetime)         { return $this->driver()->gc($maxLifetime); },

                // If the driver provides the createSid method, use it
                ( \method_exists($this->driver()->getDriver(), "createSid")
                    ? function($ip = null, $timestamp = null, $prng = null, $rand = null) {
                        return $this->driver()->createSid($ip, $timestamp, $prng, $rand);
                    }
                    : function($ip = null, $timestamp = null, $prng = null, $rand = null) {
                        return $this->createSid($ip, $timestamp, $prng, $rand);
                    } )

            ) ) {
                throw new \RuntimeException("Error calling session_set_save_handler");
            }

            $sessionName = self::$sessionName = ( self::$sessionName ?: \session_name() ?: self::_SESSION_NAME );

            // Make sure driver session name is set
            \session_name($sessionName);

            // Get the session id
            $sessionId = self::$sessionId = self::$sessionId
                ?: \session_id()
                    ?: ( $_COOKIE[ $sessionName ] ?? null )
                        ?: (
                        (\method_exists($this->driver()->getDriver(), "createSid") === true)
                            ? $this->driver()->getDriver()->createSid()
                            : $this->createSid()
                        );

            // Sets the driver session id; not all drivers will support this
            if(isset($sessionId)) {
                self::$sessionId = \session_id($sessionId);
            }

            // Starts the session or throws an exception
            if( $this->driver()->sessionStart( \array_merge(["name" => $sessionName], $options) ) !== true ) {
                throw new \RuntimeException("Error calling new Session " . $this->driver()->getName());
            }

            // The driver may have changed the session id upon session start: update it
            self::$sessionId = \session_id();
        }

        return \session_status() === \PHP_SESSION_ACTIVE;
    }

    /**
     * @param array $options
     * @return bool
     * @throws \RuntimeException
     */
    public function safeStart(array $options = []): bool
    {
        if ( \session_status() === \PHP_SESSION_DISABLED ) {
            throw new \RuntimeException("Session must be enabled", \PHP_SESSION_DISABLED);
        }

        // Session must be initalized
        if ( \session_status() === \PHP_SESSION_NONE ) {
            throw new \RuntimeException("You must start a new session", \PHP_SESSION_NONE);
        }

        if ( \session_status() === \PHP_SESSION_ACTIVE ) {
            throw new \RuntimeException("Session already open", \PHP_SESSION_ACTIVE);
        }

        return $this->start($options);
    }

    /**
     * Sets a session key to a defined value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool|void
     */
    protected function set($key, $value)
    {
        return $this->driver()->set($key, $value);
    }

    /**
     * Returns the value of a session key.
     *
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function get($key)
    {
        return $this->driver()->get($key);
    }

    /**
     * Adds a value to a session key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function add($key, $value)
    {
        return $this->driver()->add($key, $value);
    }

    /**
     * Checks if a session key is set.
     *
     * @param string $key
     * @return bool
     */
    protected function has($key): bool
    {
        return $this->driver()->has($key);
    }

    /**
     * Unsets a session key.php unset whole array
     *
     * @param string $key
     * @return bool|void
     */
    protected function forget($key)
    {
        return $this->driver()->forget($key);
    }

    /**
     * Provides a standard way of generating pseudo-secure session ids.
     *
     * @param string|null $ip
     * @param int|null $timestamp
     * @param float|null $prng
     * @param string|null $rand
     * @return string
     */
    protected function createSid(string $ip = null, int $timestamp = null, float $prng = null, string $rand = null): string
    {
        // The time of request
        $timestamp = $timestamp ?: \time();

        // Linear congruence generator value
        $prng = $prng ?: \lcg_value();

        // Pseudo-random value
        $rand = $rand ?: \substr(\str_shuffle(\md5(\microtime())), 0, 10);

        // @todo Ensure uniqueness to avoid collisions?
        // Finally, return the (unique?) session id
        return \md5($ip . $timestamp . $prng . $rand);
    }
}

