<?php/** * Tests for Jasny\DB\Record. * * The MySQL user needs to have full permissions for `dbtest`.*. * * Please configure default mysqli settings in your php.ini. * Alternatively run as `php -d mysqli.default_user=USER -d mysqli.default_pw=PASSWORD /usr/bin/phpunit` * * @author Arnold Daniels *//** */namespace Jasny\DB;use Jasny\DB\MySQL\Connection;use org\bovigo\vfs\vfsStream, org\bovigo\vfs\visitor\vfsStreamPrintVisitor;require_once __DIR__ . '\MySQL\TestCase.php';class ModelGeneratorTest extends \Jasny\DB\MySQL\TestCase{    /**     * @var \vfsStreamDirectory     */    private $root;    /**     * Call a protected method     *     * @param string $class     * @param string $name   Method name     * @param array  $args     * @return mixed     */    protected static function call($class, $name, $args)    {        $method = new \ReflectionMethod($class, $name);        $method->setAccessible(true);        return $method->invokeArgs(null, $args);    }    /**     * Get a protected property     *     * @param string $class     * @param string $name   Property name     * @return mixed     */    protected static function get($class, $name)    {        $property = new \ReflectionProperty($class, $name);        $property->setAccessible(true);        return $property->getValue(null);    }    /**     * Set a protected property     *     * @param string $class     * @param string $name   Property name     * @param mixed  $value     */    protected static function set($class, $name, $value)    {        $property = new \ReflectionProperty($class, $name);        $property->setAccessible(true);        $property->setValue(null, $value);    }    /**     * set up test environmemt     */    public function setUpCache()    {        $this->root = vfsStream::setup('cache');    }    /**     * set up test environmemt     */    public function tearDownCache()    {        foreach (spl_autoload_functions() as $fn) {            if (is_array($fn) && is_string($fn[0]) && strpos($fn[0], 'ModelGenerator') !== false) {                spl_autoload_unregister($fn);            }        }    }    //  ---------------------------------------- Tests ----------------------------------------    /**     * Test protected function ModelGenerator::getTable()     */    public function testGetTable()    {        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'getTable');        $method->setAccessible(true);        $table = $method->invoke(new ModelGenerator(),"Foo");        $this->assertEquals("Foo", $table);    }    public function testIsInternalType_True()    {        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'isInternalType');        $method->setAccessible(true);        $bool = $method->invoke(new ModelGenerator(),"int");        $this->assertEquals(true, $bool);    }    /**     * Test protected function ModelGenerator::isInternalType()     */    public function testIsInternalType_False()    {        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'isInternalType');        $method->setAccessible(true);        $bool = $method->invoke(new ModelGenerator(),"notAType");        $this->assertEquals(false, $bool);    }    /**     * Test protected function ModelGenerator::getChecksum()     *     */    public function testGetChecksum()    {        Connection::conn();        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'getChecksum');        $method->setAccessible(true);        $checksum = $method->invoke(new ModelGenerator(),"Foo");        $this->assertEquals("e0bc19b6cadc1e95644e73db5936fa21",$checksum);    }    /**     * Test protected function ModelGenerator::indent()     *     */    public function testIndent()    {        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'indent');        $method->setAccessible(true);        $indent = $method->invoke(new ModelGenerator(),'testToIndent');        $toCompare = '    testToIndent';        $this->assertEquals($indent, $toCompare);        $indent = $method->invoke(new ModelGenerator(),'testToIndent',20);        $toCompare = '                    testToIndent';        $this->assertEquals($indent, $toCompare);    }    /**     * Test protected function ModelGenerator::splitClass() with ns=0     *     */    public function testSplitClass()    {        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'splitClass');        $method->setAccessible(true);        $aaa = $method->invoke(new ModelGenerator(), 'theClass');        $this->assertEquals('theClass', $aaa[2]);        $this->assertEquals('', $aaa[1]);        $this->assertEquals('theClass', $aaa[0]);    }    /**     * Test protected function ModelGenerator::splitClass() with ns!=0     *     */    public function testSplitClass_withNs()    {        $method = new \ReflectionMethod('Jasny\DB\ModelGenerator', 'splitClass');        $method->setAccessible(true);        $aaa = $method->invoke(new ModelGenerator(), 'theClass','theNs');        $this->assertEquals('theNs\theClass', $aaa[2]);        $this->assertEquals('theNs', $aaa[1]);        $this->assertEquals('theClass', $aaa[0]);    }    /**     * Test protected function ModelGenerator::generateTable()     *     */    public function testGenerateTable()    {        $table= ModelGenerator::generateTable('Foo');        $this->assertTrue(is_int(strpos($table,'class FooTable extends Jasny\DB\MySQL\Table')));        $this->assertTrue(is_int(strpos($table,"public function getFieldTypes()")));        $this->assertTrue(is_int(strpos($table,"public function getPrimarykey()")));        $this->assertTrue(is_int(strpos($table,"'id' => 'integer',")));        $this->assertTrue(is_int(strpos($table,"'name' => 'string',")));        $this->assertTrue(is_int(strpos($table,"'ext' => 'string',")));    }    /**     * Test protected function ModelGenerator::generateTable() treat case with non-existing exception     *     */    public function testGenerateTable_Exception()    {    try{        ModelGenerator::generateTable('Boo');        $this->fail("No MySQL\Exception was thrown");    }catch(\Exception $e){        $this->assertEquals("Table 'dbtest.boo' doesn't exist", $e->getError());    }    }    /**     * Test protected function ModelGenerator::generateRecord() no namespace     *     */    public function testGenerateRecords()    {        $table = ModelGenerator::generateRecord("Foo");        $this->assertFalse(is_int(strpos($table,'namespace ns;')));        $this->assertTrue(is_int(strpos($table,"public function __construct()")));        $this->assertTrue(is_int(strpos($table,'$this->cast();')));        $this->assertTrue(is_int(strpos($table,'if (isset($this->id)) $this->id = (integer)$this->id;')));        $this->assertTrue(is_int(strpos($table,'if (isset($this->name)) $this->name = (string)$this->name;')));        $this->assertTrue(is_int(strpos($table,'if (isset($this->ext)) $this->ext = (string)$this->ext;')));        $this->assertTrue(is_int(strpos($table,'public function _setDBTable($table)')));        $this->assertTrue(is_int(strpos($table,'if (!isset($this->_dbtable)) $this->_dbtable = $table;')));    }    /**     * Test protected function ModelGenerator::generateRecord() with namespace     *     */    public function testGenerateRecords_withNamespace()    {        $table = ModelGenerator::generateRecord("Foo", "ns");        $this->assertTrue(is_int(strpos($table,'namespace ns;')));        $this->assertTrue(is_int(strpos($table,"public function __construct()")));        $this->assertTrue(is_int(strpos($table,'$this->cast();')));        $this->assertTrue(is_int(strpos($table,'if (isset($this->id)) $this->id = (integer)$this->id;')));        $this->assertTrue(is_int(strpos($table,'if (isset($this->name)) $this->name = (string)$this->name;')));        $this->assertTrue(is_int(strpos($table,'if (isset($this->ext)) $this->ext = (string)$this->ext;')));        $this->assertTrue(is_int(strpos($table,'public function _setDBTable($table)')));        $this->assertTrue(is_int(strpos($table,'if (!isset($this->_dbtable)) $this->_dbtable = $table;')));    }    /**     * Test protected function ModelGenerator::generateRecord() exception     *     */    public function testGenerateRecords_exception()    {        try{            ModelGenerator::generateRecord('Boo');            $this->fail("No MySQL\Exception was thrown");        }catch(\Exception $e){            $this->assertEquals("Table 'dbtest.boo' doesn't exist", $e->getError());        }        //try the same thing but with namespace        try{            ModelGenerator::generateRecord('Boo', 'ns');            $this->fail("No MySQL\Exception was thrown");        }catch(\Exception $e){            $this->assertEquals("Table 'dbtest.boo' doesn't exist", $e->getError());        }    }    /**     * Test enable     */    public function testEnable()    {        self::setUpCache();        ModelGenerator::enable(vfsStream::url('cache'));        $this->assertSame(vfsStream::url('cache'), self::get('Jasny\DB\ModelGenerator', 'cachePath'));        $this->assertContains(['Jasny\DB\ModelGenerator', 'autoload'], spl_autoload_functions());        self::tearDownCache();    }    /**     * Test ModelGenerator:cacheAndLoad with cachePath not set     */    public function testCacheAndLoad()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', vfsStream::url('cache'));        $class::staticExpects($this->once())            ->method('load')            ->with($this->equalTo('vfs://cache/test.php'))            ->will($this->returnValue(true));        self::call($class, 'cacheAndLoad', ['test', '<?php // test']);        //vfsStream::inspect(new vfsStreamPrintVisitor(), $this->root); // Output the virtual file structure        $this->assertTrue($this->root->hasChild('test.php'));        $this->assertEquals(file_get_contents(vfsStream::url('cache') . "/test.php"), '<?php // test');        self::tearDownCache();    }    /**     * Test ModelGenerator:cacheAndLoad with cachePath not set     */    public function testCacheAndLoad_noCachePath()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', null );        $this->assertFalse(self::call($class, 'cacheAndLoad', ['test', '<?php // test']));        self::tearDownCache();    }    /**     * Test ModelGenerator:cacheAndLoad with no code     */    public function testCacheAndLoad_noCode()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', vfsStream::url('cache'));        $this->assertFalse(self::call($class, 'cacheAndLoad', ['test.php' , null ]));        self::tearDownCache();    }    /**     * Testing ModeGenerator::loadFromCache     */    public function testLoadFromCache()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', vfsStream::url('cache'));        $file = vfsStream::url('cache/Foo.php');        file_put_contents($file,"The checksum is @checksum e0bc19b6cadc1e95644e73db5936fa21");        $this->assertTrue(self::call($class, 'loadFromCache', ['Foo']));//        vfsStream::inspect(new vfsStreamPrintVisitor(), $this->root); // Output the virtual file structure        self::tearDownCache();    }    /**     * Testing ModeGenerator::loadFromCache cachePath not set     */    public function testLoadFromCache_noCheckSum()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', vfsStream::url('cache'));        $file = vfsStream::url('cache/Foo.php');        file_put_contents($file,"The checksum is NO checksum");        $this->assertFalse(self::call($class, 'loadFromCache', ['Foo']));        self::tearDownCache();    }    /**     * Testing ModeGenerator::loadFromCache filename doesn't exist     */    public function testLoadFromCache_nonExistingFile()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', vfsStream::url('cache'));        $this->assertFalse(self::call($class, 'loadFromCache', array(null)));        self::tearDownCache();    }    /**     * Testing ModeGenerator::loadFromCache no cachePath set     */    public function testLoadFromCache_noCachePath()    {        self::setUpCache();        $class = $this->getMockClass('Jasny\DB\ModelGenerator', ['load']);        self::set($class, 'cachePath', null);        $this->assertFalse(self::call($class, 'loadFromCache', ['Foo']));        self::tearDownCache();    }}