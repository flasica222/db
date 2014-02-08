<?php
/**
 * Tests for Jasny\DB\Record.
 *
 * The MySQL user needs to have full permissions for `dbtest`.*.
 *
 * Please configure default mysqli settings in your php.ini.
 * Alternatively run as `php -d mysqli.default_user=USER -d mysqli.default_pw=PASSWORD /usr/bin/phpunit`
 *
 * @author Arnold Daniels
 */
/** */

namespace Jasny\DB;
use Jasny\DB\MySQL\Connection;

/**
 * Tests for Record (without using a DB)
 *
 * @package Test
 */
class RecordTest extends \Jasny\DB\MySQL\TestCase
{
    /**
     * Test Record::getValues
     */
    public function testGetValues()
    {
        $record = new \Bar();
        $this->assertEquals(array('id'=>null, 'description'=>'BAR', 'children'=>[]), $record->getValues());
    }

    /**
     * Test Record::setValues
     */
    public function testSetValues()
    {
        $record = new \Bar();
        $record->setValues(array('description'=>'CAFE', 'part'=>'hack'));

        $this->assertNull($record->id);
        $this->assertEquals('CAFE', $record->description);
        $this->assertEquals('u', $record->getPart());
    }

    public function testSetValues_Objects()
    {
        $record = new \Bar();
        $record->setValues(array('description'=>'CAFE', 'part'=>'hack'));
        $record->setValues(new \Foo()); //??????????????????????????

        $this->assertNull($record->id);
        $this->assertEquals( 'CAFE', $record->description);
        $this->assertEquals('u', $record->getPart());
    }

    /**
     * Test Record::getDBTable
     */
    public function testGetDBTable()
    {
        $record = new Record();
        $table = $this->getMockBuilder('Jasny\DB\Table')->disableOriginalConstructor()->getMockForAbstractClass();
        $record->_setDBTable($table);
        $this->assertSame($table, $record->getDBTable());
    }

    /**
     * Test Record::getDBTable name nor set
     */
    public function testGetDBTable_dbTableNotSet()
    {
        $record = new Record();
        Connection::conn();
        $this->assertEquals("record", $record->getDBTable());

    }

    /**
     * Test Record::save
     */
    public function testSave()
    {
        $record = new Record();
        $record->id = 10;

        $table = $this->getMockBuilder('Jasny\DB\Table')->disableOriginalConstructor()->getMockForAbstractClass();
        $record->_setDBTable($table);

        $table->expects($this->once())->method('save')->with($this->equalTo($record));
        $record->save();
    }

    public function testJSONSerialize()
    {
        Connection::conn();
        $record = new Record();
        $record->id = 10;
        $record->no = 12;

        $json = json_encode($record);
        $this->assertEquals('{"id":10,"no":12}', json_encode($record));
//        echo(json_decode($json));
    }

    public function testGetId()
    {
        $this->markTestSkipped();
        $boo = new Record();
        Connection::conn();
        $boo->_setDBTable("foo");

        $this->assertEquals("id",$boo->getId());

    }


    public function testTable()
    {
        $boo = new Record();
        Connection::conn();
        $boo->_setDBTable("foo");
        $table = $boo->getDBTable();
        $this->assertInstanceOf('Jasny\DB\MySQL\Table', $table->table());
    }

    public function testNewRecord()
    {
        $this->markTestSkipped();
        $boo = new Record();
        Connection::conn();
        $boo->_setDBTable("foo");
        $table = $boo->getDBTable()->newRecord();
        $this->assertInstanceOf('Jasny\DB\Record', $table);
    }

}
