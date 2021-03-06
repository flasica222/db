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
        $record->setValues(new \Foo());

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

    /**
     * Test Record::jsonSerialize
     */
    public function testJSONSerialize()
    {
        Connection::conn();
        $record = new Record();
        $record->id = 10;
        $record->no = 12;

        $this->assertEquals('{"id":10,"no":12}', json_encode($record));
    }

    /**
     * Test Record::__sleep
     */
    public function test__sleep()
    {
        Connection::conn();
        $record = new Record();
        $record->id = 10;
        $record->no = 12;

        $this->assertEquals('O:15:"Jasny\DB\Record":3:{s:11:" * _dbtable";N;s:2:"id";i:10;s:2:"no";i:12;}', serialize($record));
    }

    /**
     * Test Record::_setDBTable and Record::getDBTable
     */
    public function testTable()
    {
        $boo = new Record();
        Connection::conn();
        $boo->_setDBTable("foo");
        $table = $boo->getDBTable();
        $this->assertInstanceOf('Jasny\DB\MySQL\Table', $table->table());
    }

    /**
     * Test Record::newRecord
     */
    public function testNewRecord()
    {
        $this->markTestSkipped();
        $boo = new Record();
        Connection::conn();
        $boo->_setDBTable("foo");
        $table = $boo->getDBTable()->newRecord();
        $this->assertInstanceOf('Jasny\DB\Record', $table);
    }

    /**
     * Test Record::setId exception because of no identifier field
     */
    public function testSetId_exception_noIdentifierField()
    {
        $record = new Record();
        Connection::conn()->query("create table my_table (column_a integer not null, column_b integer not null, column_c varchar(50));");
        $record->_setDBTable("my_table");

        try{
            $record->setId("id");
            $this->Fail("Exception not thrown");
        } catch (\Exception $e){
            $this->assertEquals('Table '.$record->getDBTable()->getTableName().' does not have an identifier field', $e->getMessage());
        }

        Connection::conn()->query("drop table my_table;");
    }

    /**
     * Test Record::setId exception because of composite field field
     */
    public function testSetId_Exception_compositeKey()
    {
        $record = new Record();
        Connection::conn()->query("create table my_table (column_a integer not null, column_b integer not null, column_c varchar(50), primary key (column_a, column_b));");
        $record->_setDBTable("my_table");

        try{
             $record->setId("id");
        } catch (\Exception $e){
             $this->assertEquals("Table my_table has a composite identifier field", $e->getMessage());
        }

        Connection::conn()->query("drop table my_table;");
    }

    /**
     * Test Record::setId Record::getId
     */
    public function testGetId_setId()
    {
        $record = new Record();
        $record->_setDBTable('Foo');

        $record->setId("id1");

        $this->assertEquals("id1", $record->getId());
    }

    /**
     * Test Record::setId Record::getId exception for primary key
     */
    public function testGetId_exception(){

        $record = new Record();

        Connection::conn()->query("create table my_table (column_a integer not null, column_b integer not null, column_c varchar(50));");
        $record->_setDBTable("my_table");

        try{
            $record->getId();
        } catch (\Exception $e){
            $this->assertEquals("Table my_table doesn't have a primary key", $e->getMessage());
        }
    }
}
