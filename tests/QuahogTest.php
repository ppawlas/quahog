<?php
use Quahog\Quahog;

include_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class QuahogTest
 */
class QuahogTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Quahog\Quahog
     */
    protected $quahog;

    public static function setUpBeforeClass()
    {
        mkdir('/tmp/quahog');
        file_put_contents('/tmp/quahog/EICAR', base64_decode("WDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNU\nLUZJTEUhJEgrSCo=\n"));
        file_put_contents('/tmp/quahog/EICAR2', base64_decode("WDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNU\nLUZJTEUhJEgrSCo=\n"));
    }

    public static function tearDownAfterClass()
    {
        unlink('/tmp/quahog/EICAR');
        unlink('/tmp/quahog/EICAR2');
        rmdir('/tmp/quahog');
    }

    public function setUp()
    {
        $this->quahog = new Quahog('127.0.0.1:3311');
    }

    public function testConstruct()
    {
        $this->setExpectedException('Quahog\Exception\ConnectionException');

        $quahog = new Quahog('not-a-real-clam-instance');
    }

    public function testPingOK()
    {
        $result = $this->quahog->ping();

        $this->assertTrue($result);
    }

    public function testPingFail()
    {
        $quahogMock = $this->getMock('Quahog\Quahog', array('_receiveResponse'), array('127.0.0.1:3311'));
        $quahogMock->expects($this->any())->method('_receiveResponse')->will($this->returnValue('NOPE'));

        $reflection = new ReflectionClass('Quahog\Quahog');

        $method = $reflection->getMethod('_receiveResponse');
        $method->setAccessible(true);
        $method->invoke($quahogMock);

        $this->setExpectedException('Quahog\Exception\ConnectionException');

        $quahogMock->ping();
    }

    public function testVersion()
    {
        $result = $this->quahog->version();

        $this->assertStringStartsWith('ClamAV', $result);
    }

    public function testStats()
    {
        $result = $this->quahog->stats();

        $this->assertStringStartsWith('POOLS:', $result);
    }

    public function testReload()
    {
        $result = $this->quahog->reload();

        $this->assertSame('RELOADING', $result);
    }

    public function testScanFile()
    {
        $result = $this->quahog->scanFile('/tmp/quahog/EICAR');

        $this->assertSame('/tmp/quahog/EICAR: Eicar-Test-Signature FOUND', $result);
    }

    public function testMultiscanFile()
    {
        $result = $this->quahog->multiscanFile('/tmp/quahog');

        $this->assertTrue((strpos($result, 'Eicar-Test-Signature FOUND') !== false), $result);
    }

    public function testContScan()
    {
        $result = $this->quahog->contScan('/tmp/quahog');

        $this->assertStringStartsWith('/tmp/quahog/EICAR: Eicar-Test-Signature FOUND', $result);
    }

    public function testScanStream()
    {
        $stream = file_get_contents('/tmp/quahog/EICAR');

        $result = $this->quahog->scanStream($stream);

        $this->assertSame('stream: Eicar-Test-Signature FOUND', $result);
    }

    public function testShutdown()
    {
        $result = $this->quahog->shutdown();

        $this->assertSame('', $result);
    }
}