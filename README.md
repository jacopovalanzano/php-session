# Tundra\Session

The Tundra Session is a robust, high-performance PHP library for managing sessions.
Requires PHP >= 7.0

It integrates with PHP's `session_set_save_handler()` and remains fully compatible with PHP’s built-in session system.
Standard functions like `session_destroy()` and `session_write_close()` will still work but will only affect
the default driver.

You can use your webserver to set the cookie "Parted" attribute where necessary.


## Using the session handler

Start a new session using `Tundra\Session\SessionHandler::start()`

When the session starts, PHP will use the selected driver to retrieve session data.
The data retrieved is used to populate the `$_SESSION` array.   
When the session is closed using `SessionHandler::close()`, each driver will persist the session data to storage.   
If the session is closed using `session_write_close()` instead, only the default driver will persist the session data.

If you use multiple drivers, set the fastest driver as the default driver.  
This is useful if, for example, you want a fast APC session and a persistent copy of the session data in a database.

```php
$apcDriver = new \Tundra\Session\Drivers\ApcSession(); // Memory driver
$fileDriver = new \Tundra\Session\Drivers\FileSession(); // File driver
$session = new \Tundra\Session\SessionHandler();

$session->addDriver("apc_driver", $apcDriver);
$session->addDriver("file_driver", $fileDriver);

$session->setDefaultDriver("apc_driver");

$session->driver("file_driver")->open("/tmp/php", $session->getName()); // Open the session driver

$session->start(); // Start a new session: opens then reads the data from the default driver and puts it in $_SESSION (`null` if no data)

$session->set("key", "value"); // Set the session data; the equivalent of $_SESSION["key"] = "value"

$session->get("key"); // Get the session data from $_SESSION; the equivalent of $_SESSION["key"]

$session->close(); // Save the session data on each driver
```

The session data is not stored immediately when set; instead, it is saved when the session is closed:

```php
$session->close(); // Each driver will store the session data

// or

\session_write_close(); // Only the default driver will store the session data
```

You cannot set or get data while the session is closed.

```php
$session->set("key", "value"); // Fills the $_SESSION array

$session->start(); // Start the session: the $_SESSION array is now empty

$session->get("key"); // Throws an error or returns null

$session->close(); // No data to save

$session->get("key"); // Throws an error or returns null
```

### Opening and closing sessions

Opening and closing the session involves reading the data from _one_ driver and writing data to _every_ driver
available.

```php
$session->start(); // Reads the data from the default driver and puts it in $_SESSION
$session->close(); // Saves the session data on each driver & runs each driver "close" method
```

You must use the `SessionHandler::start()` to open a new session and the `SessionHandler::close()` method
to instruct each driver to store the session data, and finally close the session.

The session should be operated exclusively by the `SessionHandler`, but the PHP session, `$_SESSION` and the
driver wrapper `\Tundra\Session\SessionDriverWrapper` can do that too.

### Opening and closing multiple sessions

The `start()` method will only open the default driver storage. If you want to use multiple drivers, you must
open each driver individually using the `open()` method:
```php
$fileDriver = new \Tundra\Session\Drivers\FileSession();
$apcDriver = new \Tundra\Session\Drivers\ApcSession();
$sqlDriver = new YourSQLSession();
$session = new \Tundra\Session\SessionHandler();

$session->addDriver("apc_driver", $apcDriver);
$session->addDriver("file_driver", $fileDriver);
$session->addDriver("sql_driver", $sqlDriver);

$session->setDefaultDriver("apc_driver");

$session->driver("file_driver")->open("/tmp/file"); // Open the file driver
$session->driver("sql_driver")->open("/tmp/file"); // Open the SQL driver

$session->start(); // Let the default driver open the session
$session->close(); // Save the session data on each driver
```
You can also use the `openAll()` method. Use `openAll()` **before** starting the session:
```php
$session->openAll("/tmp/php", $session->getName());

$session->start();
$session->close(); // Save the session data on each driver
```
Bear in mind that the default driver will always be opened on session start; that may cause unexpected results.

### Close the session
Depending on the driver, session data may not be readable or writable while the session is closed, often due to file
locks or database transactions.

## Example

Create a file session:

```php
<?php

/**
 * Start a new file session:
 */
$session = new \Tundra\Session\SessionHandler();

$fileDriver = new \Tundra\Session\Drivers\FileSession(); // Driver

$session->setDefaultDriver("apc_driver"); // Always set a default driver

$session->addDriver($fileDriver);

$session->start(); // Start the session
```

You should check if the session was started:
```php

if(! $session->isStarted()) {
    $session->start();
}

```

Create an "APC" session in-memory. The session data will be lost on restart:
```php
<?php

/**
 * Start a new APC session (sessions will be lost on restart):
 */
$session = new \Tundra\Session\SessionHandler();

$apcDriver = new \Tundra\Session\Drivers\ApcSession();

$session->addDriver($apcDriver);

// Start the session
if(! $session->isStarted()) {
    $session->start();
}
```

Use multiple drivers:
```php
<?php

/**
 * Set multiple drivers
 */
$session = new \Tundra\Session\SessionHandler();

$apcDriver = new \Tundra\Session\Drivers\ApcSession();
$fileDriver = new \Tundra\Session\Drivers\FileSession();

$session->addDriver("apc_driver", $apcDriver);
$session->addDriver("file_driver", $fileDriver);
$session->setDefaultDriver("apc_driver"); // Always set a default driver

// Start the session
if(! $session->isStarted()) {
    // Open all the drivers
    $session->openAll($path, $sessionName);
    
    $session->start();
}

```

### Create your own driver

```php

class SQLSession implements \SessionHandlerInterface
{

    public function open($path, $sessionName)
    {
        // ...
    }
    
    public function close()
    {
        // ...
    }
    
    public function read($sessionId)
    {
        // ...
    }
    
    public function write($sessionId, $data)
    {
        // ...
    }
    
    public function destroy($sessionId)
    {
        // ...
    }
    
    public function gc($maxlifetime)
    {
        // ...
    } 

}


```

Implement the `SQLSession` driver:

```php

$session = new \Tundra\Session\SessionHandler();
$sqlDriver = new SQLSession();
$session->addDriver($sqlDriver);
$session->start();

$session->set("foo", "bar");
$session->get("foo");

$session->close();

```

You can call custom driver methods using `driver()`:

```php
class SQLSession implements \SessionHandlerInterface
{
    // ...
    
    public function getData($key) {
        
        // ...
        
        if(! $connection->isOpen()) {
            $connection->open();
        }
        
        $data = $connection->select($key)->from("table")->get();
        $connection->close();
        
        return $data;
    }

}
```

Call the getData() method:

```php
$session->driver()->getData("foo");
```

### Handling session data

#### Session destroy
When you destroy the session, the session stops running, but the global `$_SESSION` variable remains untouched.
However, the session storage is cleared—if using file-based storage, for example, the session file is deleted;
if using an SQL database driver, the session record is removed.
The session data remains available within PHP until the script execution ends.

#### Session invalidate
When you invalidate the session, the session remains active, but the global `$_SESSION` variable is cleared,
and a new session ID is generated. PHP will no longer have access to the previous session data, but the
storage remains untouched. This can lead to excessive session files or database records, potentially increasing
storage size. Ensure you genuinely need a new session without removing the old one from storage.

⚠️ Using `invalidate` can lead to huge database size or a lot of session files. Make sure you intend to
generate a new session, without deleting the previous session from storage.

#### Session clear
When you clear the session, it remains active, but the global `$_SESSION` variable is emptied while keeping the
same session ID. PHP will no longer have access to the session data. This results in an empty session file or
database record, causing all data to be lost when the session is closed, though the record or file 
itself will persist.

#### Session erase
If you want to create a fresh new session and destroy the storage data, use `erase()`.
This will stop the session, clear the session data from both the storage and the PHP system, and
generate a new session ID.
