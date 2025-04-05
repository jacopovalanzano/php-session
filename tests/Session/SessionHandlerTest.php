<?php

require __DIR__ . "../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;

class SessionHandlerTest extends TestCase
{

    private $sessionHandler;

    private $fileSessionDriver;

    private $apcSessionDriver;

    public function setUp() {

        // Create a file based session driver
        #$fileSessionDriver = $this->createMock("\Tundra\Session\Drivers\FileSession");
        $this->fileSessionDriver = new \Tundra\Session\Drivers\FileSession();
        $this->assertInstanceOf("\Tundra\Session\Drivers\FileSession", $this->fileSessionDriver);

        // Create an "apc" based session driver
        $this->apcSessionDriver = $this->createMock("\Tundra\Session\Drivers\ApcSession");
        $this->assertInstanceOf("\Tundra\Session\Drivers\ApcSession", $this->apcSessionDriver);

        // Get the session handler
        $this->sessionHandler = new \Tundra\Session\SessionHandler();
        $this->assertInstanceOf("\Tundra\Session\SessionHandler", $this->sessionHandler);

        // Add the session drivers
        $this->sessionHandler->addDriver("file", $this->fileSessionDriver);
        $this->sessionHandler->addDriver("apc", $this->apcSessionDriver);
        $this->sessionHandler->setDefaultDriver("file");

        // Sets the default session (file)
        $this->assertInstanceOf("\SessionHandlerInterface", $this->sessionHandler->driver()->getDriver());
    }

    public function tearDown() {
        $this->sessionHandler->destroy();
        $this->sessionHandler = null;
    }

    public function testDriver()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertInstanceOf("\Tundra\Session\SessionDriverWrapper", $this->sessionHandler->driver());
    }

    public function testGetDefaultDriverName()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertSame("file", $this->sessionHandler->getDefaultDriverName());
    }

    public function testSessionStart()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $sessionHandler = $this->sessionHandler;
        $this->assertTrue($sessionHandler->start());
        $this->sessionHandler->destroy();
    }

    /**
     * The "destroy" method requires the $sessionId parameter, it is filled automatically by the SessionHandler
     * when an empty value is supplied.
     *
     * @param string $sessionId
     * @return bool
     */
    public function testDestroy()
    {
        $sessionHandler = $this->sessionHandler;

        if($sessionHandler->isStarted()) {
            $this->assertTrue($sessionHandler->destroy());
        } else {
            $this->assertTrue($sessionHandler->start());
            $this->assertTrue($sessionHandler->destroy());
        }
    }

    public function testIsStarted()
    {
        if($this->sessionHandler->isStarted() === true) {
            $this->sessionHandler->destroy();
            $this->sessionHandler->start();
            $this->assertTrue($this->sessionHandler->isStarted());
            $this->sessionHandler->destroy();
        } else {
            $this->sessionHandler->start();
            $this->assertTrue($this->sessionHandler->isStarted());
            $this->sessionHandler->destroy();
        }
    }

    public function testHas()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertFalse($this->sessionHandler->has("NON_EXISTENT_KEY"));
        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", "EXISTENT_KEY"));
        $this->assertTrue($this->sessionHandler->has("EXISTENT_KEY"));
    }

    public function testAdd()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", []));
        $this->assertNull($this->sessionHandler->add("EXISTENT_KEY", "EXISTENT_VALUE",));
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
    }

    public function testSet()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", []));
        $this->assertEquals([], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]));
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
    }

    public function testSessionClose()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);
        $this->assertFalse($this->sessionHandler->close());
    }

    public function testSessionUnset()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->sessionUnset();
        $this->expectException(\InvalidArgumentException::class);
        $this->assertEquals([], $this->sessionHandler->driver()->get("EXISTENT_KEY"));
    }

    public function testSessionDestroy()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]);
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->sessionDestroy($this->sessionHandler->getId());
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->driver()->get("EXISTENT_KEY"));
        #$this->assertFalse(is_file($this->sessionHandler->driver()->sessionDriver->path . "/tdr_sess_" . $this->sessionHandler->getId())); // members must be declared "public" for this test to work
    }

    public function testSetNewDriver()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->sessionHandler->addDriver("file2", new \Tundra\Session\Drivers\FileSession());
        $this->assertInstanceOf("\SessionHandlerInterface", $this->sessionHandler->driver("file2"));
    }

    public function testSetName()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertEquals("__TUNDRA_PHPSESSID", $this->sessionHandler->getName());
        $this->sessionHandler->destroy();
        $this->sessionHandler->setName("NEW_SESSION_NAME");
        $this->assertEquals("NEW_SESSION_NAME", $this->sessionHandler->getName());
    }

    public function testGetName()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertEquals("NEW_SESSION_NAME", $this->sessionHandler->getName());
    }

    public function testStart()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertTrue($this->sessionHandler->start());
        $this->sessionHandler->destroy();
    }

    public function testClear()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]);
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->clear();
        $this->expectException(\InvalidArgumentException::class);
        $this->assertEquals([], $this->sessionHandler->driver()->get("EXISTENT_KEY"));
    }

    public function testInvalidate()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->sessionHandler->start();
        $sessionId = $this->sessionHandler->getId();
        $this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]);
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->invalidate();
        $this->assertNotEquals($sessionId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($sessionId);
        $this->assertNotEquals($sessionId, $this->sessionHandler->getId());
    }

    public function testRegenerate()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $currentId = $this->sessionHandler->getId();
        $this->assertEmpty($currentId);
        $this->sessionHandler->start();
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->regenerate();
        $this->assertNotEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testInit()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertFalse($this->sessionHandler->destroy());
        $this->assertTrue($this->sessionHandler->start());
        $this->assertTrue($this->sessionHandler->destroy());
    }

    public function testSessionRegenerateId()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertEquals(false, $this->sessionHandler->isStarted());
        $this->assertEquals(false, session_status() === PHP_SESSION_ACTIVE);
        $this->sessionHandler->start();
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->sessionRegenerateId();
        $this->assertNotEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testSessionStatus()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertEquals(1, $this->sessionHandler->sessionStatus());
        $this->sessionHandler->start();
        $this->assertEquals(2, $this->sessionHandler->sessionStatus());
        $this->sessionHandler->destroy();
    }

    public function testGetId()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $currentId = $this->sessionHandler->getId();
        $this->assertEmpty($currentId);
        $this->sessionHandler->start();
        $this->assertNotEmpty($this->sessionHandler->getId());
        $this->sessionHandler->destroy($currentId);
    }

    public function testSetId()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $currentId = $this->sessionHandler->getId();
        $this->assertEmpty($currentId);
        $this->sessionHandler->start();
        $this->assertNotEmpty($this->sessionHandler->getId());
        $this->sessionHandler->destroy();

        $this->sessionHandler->setId("TEST_SESSION_ID");
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->start();
        $this->assertEquals($currentId, "TEST_SESSION_ID");
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testSetDefaultDriver()
    {
        // Make sure the session is not started but active
        $this->assertFalse($this->sessionHandler->isStarted());
        $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);

        $this->assertEquals("file", $this->sessionHandler->getDefaultDriverName());
        $newSessionHandler = new \Tundra\Session\SessionHandler();
        $this->expectException(\BadMethodCallException::class);
        $this->assertEquals("", $newSessionHandler->getDefaultDriverName());
        $newSessionHandler->setDefaultDriver("newTestDriver");
    }

}
