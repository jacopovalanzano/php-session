<?php

namespace Tundra\Session;

use \BadMethodCallException;
use \RuntimeException;

/**
 * Class SessionManager
 *
 * @author Jacopo Valanzano
 * @package Tundra\Session
 * @license MIT
 */
class SessionManager extends AbstractSession
{
    /**
     * The driver (name) used during the session transaction.
     *
     * @var string
     */
    protected $defaultDriverName;

    /**
     * The session driver(s) used to store and retrieve session data.
     *
     * @var SessionDriverWrapper[]
     */
     protected $driver;

    /**
     * Sets the current driver and retrieves the driver instance.
     *
     * @param string|null $name
     * @return SessionDriverWrapper
     * @throws BadMethodCallException
     */
    public function driver(string $name = null): SessionDriverWrapper
    {
        $this->defaultDriverName = $name ?: $this->defaultDriverName;

        // If no driver of this type ($defaultDriverName) exists, throw an exception.
        if(! isset($this->driver[$this->defaultDriverName])) {
            throw new BadMethodCallException(static::class ." does not have a definition for the driver '$this->defaultDriverName'");
        }

        // Return the driver instance ( of SessionDriverWrapper containing the actual session driver )
        return $this->driver[$this->defaultDriverName];
    }

    /**
     * Adds a new driver.
     *
     * @param string $name
     * @param \SessionHandlerInterface $driver
     * @return \SessionHandlerInterface
     * @throws RuntimeException
     */
    public function addDriver(string $name, \SessionHandlerInterface $driver): \SessionHandlerInterface
    {
        if(isset($this->driver[$name])) {
            throw new RuntimeException(\get_called_class()." an entry for the Session driver ['$name'] already exists.");
        }

        return $this->driver[$name] = new SessionDriverWrapper($driver);
    }

    /**
     * @param string $name
     * @return void
     * @throws RuntimeException
     */
    public function setDefaultDriver(string $name)
    {
        if(! isset($this->driver[$name])) {
            throw new RuntimeException(\get_called_class()." does not have a definition for the driver '$name'");
        }

        $this->defaultDriverName = $name;
    }

    /**
     * The current driver (name) being used for transactions.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriverName;
    }

    /**
     * Returns an array containing all the session drivers.
     *
     * @return SessionDriverWrapper[]
     */
    public function getAllDrivers(): array
    {
        return $this->driver;
    }

    /**
     * @param string $driverName
     * @return bool
     */
    public function closeDriver(string $driverName) {
        return $this->driver[$driverName]->close();
    }

    /**
     * @param string $driverName
     * @return void
     */
    public function unsetDriver(string $driverName) {
        unset($this->driver[$driverName]);
    }

    /**
     * @param string|null $sessionId
     * @return bool
     */
    public function destroy($sessionId = null): bool
    {
        $sessionId = $sessionId ?: self::getId();

        foreach ($this->getAllDrivers() as $driverName => $sessionDriver) {
            if($driverName === $this->defaultDriverName) continue;
            $sessionDriver->destroy($sessionId);
        }

        if( \session_status() === \PHP_SESSION_ACTIVE ) { return \session_destroy(); } // Destroys the default driver

        return false;
    }

    /**
     * @return bool|void
     * @throws BadMethodCallException
     */
    public function close()
    {

        if(! $this->getId()) {
            foreach ($this->driver as $sessionDriver) {
                $sessionDriver->close();
            }

            return \session_abort();
        }

        $data = \session_encode();

        foreach ($this->driver as $sessionDriver) {
            $sessionDriver->write($this->getId(), $data);
            $sessionDriver->close();
        }

        return \session_abort();
    }

    /**
     * @param int $maxLifetime
     * @return int|bool
     * @throws BadMethodCallException
     */
    public function gc($maxLifetime = 86400)
    {
        return $this->driver()->gc($maxLifetime);
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     * @throws BadMethodCallException
     */
    public function open($savePath, $sessionName): bool
    {
        return $this->driver()->open($savePath, $sessionName);
    }

    /**
     * @param string $sessionId
     * @return string|false
     * @throws BadMethodCallException
     */
    public function read($sessionId): string
    {
        return $this->driver()->read($sessionId);
    }

    /**
     * @param string $sessionId
     * @param string $data
     * @return bool
     * @throws BadMethodCallException
     */
    public function write($sessionId, $data): bool
    {
        return $this->driver()->write($sessionId, $data);
    }

    /**
     * Closes the session with all available session drivers.
     *
     * @return void
     */
    public function closeAll()
    {
        foreach($this->getAllDrivers() as $driver ) {
            $driver->close();
        }
    }

    /**
     * Destroys the session with all available session drivers.
     *
     * @param string|null $sessionId
     * @return void
     */
    public function destroyAll($sessionId = null)
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->destroy($sessionId ?: $this->getId());
        }
    }

    /**
     * Garbage-collection with all available session drivers.
     *
     * @param int $maxLifetime
     * @return void
     */
    public function gcAll(int $maxLifetime = 86400)
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->gc($maxLifetime);
        }
    }

    /**
     * Opens the session with all available session drivers.
     *
     * @param string $path
     * @param string $sessionName
     * @return void
     */
    public function openAll(string $path, string $sessionName)
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->open($path, $sessionName);
        }
    }

    /**
     * Reads from session with all available session drivers.
     *
     * @param string $sessionId
     * @return array
     */
    public function readAll(string $sessionId): array
    {
        $reads = [];

        foreach($this->getAllDrivers() as $driver) {
            $reads[] = $driver->read($sessionId);
        }

        return $reads;
    }

    /**
     * Writes to the session with all available session drivers.
     *
     * @param string $sessionId
     * @param string $data
     * @return void
     */
    public function writeAll(string $sessionId, string $data)
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->write($sessionId, $data);
        }
    }

    /**
     * @return false|string
     */
    public function getName() {
        return \session_name();
    }

    /**
     * Sets the new session name.
     * Returns the old session name on success.
     *
     * @param string $name
     * @return mixed
     * @throws \RuntimeException
     */
    public function setName(string $name)
    {
        // Before PHP 7.2, php session throws a fatal error if the session name
        // is changed while the session is active
        if(\session_status() === \PHP_SESSION_ACTIVE) {
            throw new \RuntimeException("Cannot change session name when session is active");
        }

        return self::$sessionName = \session_name($name);
    }

    /**
     * Returns the current session id.
     *
     * @return false|string
     */
    public function getId()
    {
        return \session_id();
    }

    /**
     * Sets the (new) session id.
     *
     * @param string|null $sessionId
     * @return false|string
     */
    public function setId(string $sessionId = null)
    {
        self::$sessionId = \session_id($sessionId);

        return self::$sessionId;
    }

    /**
     * Executes the methods of the driver wrapper.
     *
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws RuntimeException|BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if(\method_exists($this->driver(), $method)) {
            return $this->driver()->{$method}(...$parameters);
        }

        throw new RuntimeException(\get_called_class()." does not have a method '$method'");
    }

}
