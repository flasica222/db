<?php

/**
 * Tests for Jasny\DB\MySQL\Table.
 *
 * The MySQL user needs to have full permissions for `dbtest`.*.
 *
 * Please configure default mysqli settings in your php.ini.
 * Alternatively run as `php -d mysqli.default_user=USER -d mysqli.default_pw=PASSWORD /usr/bin/phpunit`
 *
 * @author Arnold Daniels
 */
/** */

namespace Jasny\DB\MySQL;

require_once __DIR__ . '/TestCase.php';

/**
 * Tests for MySQL\Table.
 *
 * @package Test
 * @subpackage MySQL
 */
class TableTest extends TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->table = Table::factory('foo', $this->db);
    }

    /**
     * Test Table::getDefaultConnection
     */
    public function testGetDefaultConnection()
    {
        $this->assertSame($this->db, Table::getDefaultConnection());
        $this->assertSame($this->db, \Jasny\DB\Table::getDefaultConnection());

        $newdb = $this->getMockBuilder('Jasny\DB\MySQL\Connection')->disableOriginalConstructor()->getMock();
        \Jasny\DB\Table::$defaultConnection = $newdb;

        $this->assertSame($this->db, Table::getDefaultConnection());
        $this->assertSame($newdb, \Jasny\DB\Table::getDefaultConnection());
    }


    /**
     * Test Table::factory
     */
    public function testFactory()
    {
        $foo = Table::factory('foo', $this->db);
        $this->assertInstanceOf('Jasny\DB\MySQL\Table', $foo);
        $this->assertSame($this->db, $foo->db());
        $this->assertSame('foo', $foo->getTableName());

        $foo_bar = Table::factory('foo_bar', $this->db);
        $this->assertInstanceOf('FooBarTable', $foo_bar);
        $this->assertSame('foo_bar', $foo_bar->getTableName());

        $this->assertSame($foo, $this->db->table('foo'));
    }

    /**
     * Test Table::factory with a model namespace
     */
    public function testFactory_Ns()
    {
        $this->db->setModelNamespace('Test');

        $table = Table::factory('foo', $this->db);
        $this->assertInstanceOf('Test\FooTable', $table);
        $this->assertSame('foo', $table->getTableName());
    }

    /**
     * Test Table::factory with the default DB
     */
    public function testFactory_DefaultConnection()
    {
        $foo = Table::factory('foo');
        $this->assertInstanceOf('Jasny\DB\MySQL\Table', $foo);
        $this->assertSame($this->db, $foo->db());
    }

    /**
     * Test Table::factory with class names
     */
    public function testFactory_Class()
    {
        $foo = Table::factory('Foo', $this->db);
        $this->assertInstanceOf('Jasny\DB\MySQL\Table', $foo);
        $this->assertSame($this->db, $foo->db());
        $this->assertSame('foo', $foo->getTableName());

        $foo_bar = Table::factory('FooBar', $this->db);
        $this->assertInstanceOf('FooBarTable', $foo_bar);
        $this->assertSame('foo_bar', $foo_bar->getTableName());
    }

    /**
     * Test Table::factory with class names using a model namespace
     */
    public function testFactory_ClassNS()
    {
        $this->db->setModelNamespace('Test');

        $foo = Table::factory('Test\Foo', $this->db);
        $this->assertInstanceOf('Jasny\DB\MySQL\Table', $foo);
        $this->assertSame($this->db, $foo->db());
        $this->assertSame('foo', $foo->getTableName());
    }


    /**
     * Test Table::getRecordClass
     */
    public function testGetClass()
    {
        $foo = Table::factory('foo', $this->db);
        $this->assertSame('Foo', $foo->getRecordClass());

        $foo_bar = Table::factory('foo_bar', $this->db);
        $this->assertSame('Jasny\DB\Record', $foo_bar->getRecordClass());
    }

    /**
     * Test Table::getRecordClass with a model namespace
     */
    public function testGetClass_Ns()
    {
        $this->db->setModelNamespace('Test');

        $table = Table::factory('foo', $this->db);
        $this->assertSame('Test\Foo', $table->getRecordClass());
    }

    /**
     * Test Table::getDefaults
     */
    public function testGetDefaults()
    {
        $this->assertSame(array('id'=>null, 'name'=>null, 'ext'=>'tv'), $this->table->getDefaults());
    }

    /**
     * Test Table::getDefaults with different kind of columns
     */
    public function testGetDefaults_Extended()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test Table::getPrimarykey
     */
    public function testgetPrimarykey()
    {
        $this->assertSame('id', $this->table->getPrimarykey());
    }

    /**
     * Test Table::getPrimarykey with combined primary keys
     */
    public function testgetPrimarykey_Combined()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test Table::fetchAll
     */
    public function testFetchAll()
    {
        $records = $this->table->fetchAll();
        $this->assertEquals(5, count($records));

        $this->assertInstanceOf('Foo', $records[0]);
        $this->assertSame($this->table, $records[0]->getDBTable());

        $this->assertEquals(1, $records[0]->id);
        $this->assertEquals('Foo', $records[0]->name);
        $this->assertEquals('tv', $records[0]->ext);

        $this->assertEquals(2, $records[1]->id);
        $this->assertEquals(3, $records[2]->id);
        $this->assertEquals(4, $records[3]->id);
        $this->assertEquals(5, $records[4]->id);
    }

    /**
     * Test Table::fetch
     */
    public function testFetch()
    {
        $record = $this->table->fetch(3);

        $this->assertInstanceOf('Foo', $record);
        $this->assertSame($this->table, $record->getDBTable());

        $this->assertEquals(3, $record->id);
        $this->assertEquals('Zoo', $record->name);
        $this->assertEquals('tv', $record->ext);

        $this->assertNull($this->table->fetch(99));
    }

    /**
     * Test Table::fetch with a filter
     */
    public function testFetch_Filter()
    {
        $record = $this->table->fetch(array('name'=>'Zoo'));

        $this->assertInstanceOf('Foo', $record);
        $this->assertSame($this->table, $record->getDBTable());

        $this->assertEquals(3, $record->id);
        $this->assertEquals('Zoo', $record->name);
        $this->assertEquals('tv', $record->ext);

        $this->assertNull($this->table->fetch(array('name'=>'No')));
    }

    /**
     * Test Record::fetch
     */
    public function testFetch_UsingRecord()
    {
        $record = \Foo::fetch(3);

        $this->assertInstanceOf('Foo', $record);
        $this->assertSame($this->table, $record->getDBTable());

        $this->assertEquals(3, $record->id);
        $this->assertEquals('Zoo', $record->name);
        $this->assertEquals('tv', $record->ext);

        $this->assertNull($this->table->fetch(array('name'=>'No')));
    }

    /**
     * Test Table::fetchValue()
     */
    public function testFetchValue()
    {
        $record = $this->table->fetchValue("name",2);
        $this->assertEquals("Bar",$record);
    }

    /**
     * Test Table::fetchValue() argument $id not used in the DB
     */
    public function testFetchValue_nonExistentRecord()
    {
        $record = $this->table->fetchValue("name",99999);
        $this->assertEquals(null, $record);
    }

    /**
     * Test Table::fetchValue() argument $field not found in DB
     */
    public function testFetchValue_nonExistentField()
    {
        try
        {
            $this->table->fetchValue("nonExistent",1);
            $this->fail("No MySQL\Exception was thrown");
        }
        catch(\Exception $e)
        {
            $this->assertEquals("Unknown column 'nonExistent' in 'field list'", $e->getError());
        }
    }

    /**
     * Test Table::save
     */
    public function testSave()
    {
        self::$reuse_db = false;

        $record = $this->table->fetch(1);
        $record->name = 'KLM';
        $id = $this->table->save($record);

        $this->assertSame(1, $id);
        $this->assertEquals(1, $record->id);

        $result = $this->db->query("SELECT * FROM foo WHERE id = 1");
        $this->assertEquals(array('id'=>1, 'name'=>'KLM', 'ext'=>'tv'), $result->fetch_assoc());
    }

    /**
     * Test Table::save with an array of values
     */
    public function testSave_Values()
    {
        self::$reuse_db = false;

        $id = $this->table->save(array('id'=>1, 'name'=>'KLM'));
        $this->assertEquals(1, $id);

        $result = $this->db->query("SELECT * FROM foo WHERE id = 1");
        $this->assertEquals(array('id'=>1, 'name'=>'KLM', 'ext'=>'tv'), $result->fetch_assoc());
    }

    /**
     * Test Table::save with a new record
     */
    public function testSave_Insert()
    {
        return;
        self::$reuse_db = false;

        $record = new \Foo();
        $record->name = 'MAN';
        $record->ext = 'mu';
        $ret = $this->table->save($record);

        $this->assertSame($record, $ret);
        $this->assertEquals(6, $record->id);

        $result = $this->db->query("SELECT * FROM foo WHERE id = 6");
        $this->assertEquals(array('id'=>6, 'name'=>'MAN', 'ext'=>'mu'), $result->fetch_assoc());
    }

    /**
     * Test Table::save with a new record
     */
    public function testSave_Insert_Values()
    {
        return;
        self::$reuse_db = false;

        $record = $this->table->save(array('name'=>'MAN', 'ext'=>'mu'));
        $this->assertEquals(6, $record->id);
        $this->assertEquals('MAN', $record->name);
        $this->assertEquals('mu', $record->ext);

        $result = $this->db->query("SELECT * FROM foo WHERE id = 6");
        $this->assertEquals(array('id'=>6, 'name'=>'MAN', 'ext'=>'mu'), $result->fetch_assoc());
    }

    /**
     * Test Table::save to a table without an auto_increment field
     */
    public function testSave_WithoutAutoinc()
    {
        $this->markTestIncomplete();
    }
//
//    public function testCount()
//    {
//        echo(count(100));
//    }
}
