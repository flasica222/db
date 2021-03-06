<?php
/**
 * Jasny DB - A DB layer for the masses.
 *
 * @author  Arnold Daniels <arnold@jasny.net>
 * @license https://raw.github.com/jasny/DB-MySQL/master/LICENSE MIT
 * @link    https://jasny.github.com/DB-MySQL
 */
/** */
namespace Jasny\DB\MySQL;

use \Jasny\DB\Record;

/**
 * DB table gateway.
 *
 * @package MySQL
 */
class Table extends \Jasny\DB\Table
{
    /**
     * PHP type for each MySQL field type.
     * @var array
     */
    public static $castTypes = array(
        'bit' => 'integer',
        'bit(1)' => 'boolean',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'tinyint(1)' => 'boolean',
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'mediumint' => 'integer',
        'int unsigned' => 'integer',
        'int' => 'string',      // Might be bigger that PHP signed integers on 32 bit
        'integer' => 'string',  // "
        'bigint' => 'string',   // "
        'decimal' => 'float',
        'dec' => 'float',
        'numeric' => 'float',
        'fixed' => 'float',
        'float' => 'float',
        'double' => 'float',
        'double precision' => 'float',
        'real' => 'float',
        'date' => 'DateTime',
        'datetime' => 'DateTime',
        'timestamp' => 'DateTime',
        'time' => 'DateTime',
        'year' => 'integer',
        'char' => 'string',
        'varchar' => 'string',
        'binary' => 'string',
        'varbinary' => 'string',
        'tinyblob' => 'string',
        'tinytext' => 'string',
        'blob ' => 'string',
        'text' => 'string',
        'mediumblob' => 'string',
        'mediumtext' => 'string',
        'longblob' => 'string',
        'longtext' => 'string',
        'enum' => 'string',
        'set' => 'array',

        'geometry' => 'string',
        'point' => 'string',
        'linestring' => 'string',
        'polygon' => 'string',
        'multipoint' => 'string',
        'multilinestring' => 'string',
        'multipolygon' => 'string',
        'geometrycollection' => 'string'
    );

    /**
     * Default values
     * @var array
     */
    protected $fieldDefaults;

    /**
     * PHP type for each field
     * @var array
     */
    protected $fieldTypes;

    /**
     * Primary key field name
     * @var string
     */
    protected $primarykey;


    /**
     * Return DB connection
     *
     * @return Connection
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * Set the DB table to this table for all records.
     *
     * @param Record|Record[] $records
     * @return Record|Record[]
     */
    protected final function setDBTable($records)
    {
        if (!isset($records)) return null;

        if (is_array($records)) {
            foreach ($records as $record) $record->_setDBTable($this);
        } else {
            $records->_setDBTable($this);
        }

        return $records;
    }


    /**
     * Determine default values, field types and indentifier.
     */
    protected function describe()
    {
        $fields = $this->db()->fetchAll("DESCRIBE " . $this->db->backquote($this->getTableName()), MYSQLI_ASSOC);

        $fieldDefaults = array();
        $types = array();
        $primarykey = array();

        foreach ($fields as $field) {
            $type = self::getCastType($field['Type']);
            $value = $field['Default'];
            if ($type == 'DateTime' && $value == 'CURRENT_TIMESTAMP') $value = 'now';

            $fieldDefaults[$field['Field']] = $value;
            $types[$field['Field']] = $type;
            if ($field['Key'] == 'PRI') $primarykey[] = $field['Field'];
        }

        if (!isset($this->fieldDefaults)) $this->fieldDefaults = $fieldDefaults;
        if (!isset($this->fieldTypes)) $this->fieldTypes = $types;
        if (!isset($this->primarykey)) $this->primarykey = count($primarykey) <= 1 ? reset($primarykey) : $primarykey;
    }

    /**
     * Get all the default value for each field for this table.
     *
     * @return array
     */
    public function getFieldDefaults()
    {
        if (!isset($this->fieldDefaults)) $this->describe();
        return $this->fieldDefaults;
    }

    /**
     * Get the php type for each field of this table.
     *
     * @return array
     */
    public function getFieldTypes()
    {
        if (!isset($this->fieldTypes)) $this->describe();
        return $this->fieldTypes;
    }

    /**
     * Get primary key.
     *
     * @return string
     */
    public function getPrimarykey()
    {
        if (!isset($this->primarykey)) $this->describe();
        return $this->primarykey ?: null;
    }


    /**
     * Get the query to return all records of this table.
     *
     * @return Query
     */
    protected function getQuery()
    {
        $tbl = Query::backquote($this->getTableName());
        
        if ($this->getPrimarykey()) $orderby = "ORDER BY "
            . join(', ', array_map([__NAMESPACE__ . '\Query', 'backquote'], (array)$this->getPrimarykey()));
        
        return new Query("SELECT $tbl.* FROM $tbl $orderby");
    }

    /**
     * Build a filter for an id or insert table name for filter fields.
     *
     * @param int|array $filter  ID or filter
     * @return array
     */
    protected function buildFilter($filter)
    {
        if (!is_array($filter)) {
            if (is_array($this->getPrimarykey())) {
                throw new \Exception("No or combined primary key. Please pass a filter as associated array.");
            }

            return array(Query::backquote($this->getTableName() . '.' . $this->getPrimarykey()) => $filter, Query::BACKQUOTE_SMART);
        }

        $where = array();
        $tbl = Query::backquote($this->getTableName());
        $regex = '#(?<!\.)`(' . join('|', array_map('preg_quote', array_keys($this->getFieldDefaults()))) . ')`#';

        foreach ($filter as $key=>$value) {
            if (is_int($key)) $value = preg_replace($regex, "{$tbl}.$1", Query::backquote($value, Query::BACKQUOTE_SMART));
             else $key = preg_replace($regex, "{$tbl}.$1", Query::backquote($key, Query::BACKQUOTE_SMART));

            $where[$key] = $value;
        }

        return $where;
    }

    /**
     * Fetch all records of the table.
     *
     * @param array $filter
     * @return array
     */
    public function fetchAll(array $filter=array())
    {
        $query = $this->getQuery()->where($this->buildFilter($filter));
        $records = $this->db()->fetchAll($query, $this->getRecordClass());

        return $this->setDBTable($records);
    }

    /**
     * Count all records of the table.
     *
     * @param array $filter
     * @return int
     */
    public function count(array $filter=array())
    {
        $query = $this->getQuery()->count()->where($this->buildFilter($filter));
        return $this->db()->fetchValue($query);
    }

    /**
     * Load a record from the DB
     *
     * @param int|array $id  ID or filter
     * @return Record
     */
    public function fetch($id)
    {
        $query = $this->getQuery()->where($this->buildFilter($id))->limit(1);
        $record = $this->db()->fetchOne($query, $this->getRecordClass());

        return $this->setDBTable($record);
    }

    /**
     * Fetch a single value from the DB
     *
     * @param string    $field  Field name
     * @param int|array $id     ID or filter
     * @return mixed
     */
    public function fetchValue($field, $id)
    {
        $query = $this->getQuery()->columns($field, Query::REPLACE)->where($this->buildFilter($id))->limit(1);
        $value = $this->db()->fetchValue($query);

        $types = $this->getFieldTypes();
        return isset($types[$field]) ? static::castValue($value, $types[$field]) : $value;
    }


    /**
     * Get a filter to fetch a record based on values.
     *
     * @param array $values
     * @return array
     */
    protected function getFilterForValues($values)
    {
        $pk = $this->getPrimarykey();

        // PK on one field
        if (!is_array($pk)) {
            return isset($values[$pk]) ? array($pk => $values[$pk]) : null;
        }

        // PK on multiple fields
        $filter = array();
        foreach ($pk as $field) {
            if (!isset($values[$field])) return null;
            $filter[$field] = $values[$field];
        }

        return $filter;
    }

    /**
     * Save the record to the DB.
     *
     * @param Record|array $record  Record or array with values
     * @return int  id
     */
    public function save($record)
    {
        $values = $record instanceof Record ? $record->getValues() : (array)$record;
        $values = array_intersect_key($values, $this->getFieldDefaults());

        $id = $this->db()->save($this->getTableName(), $values);

        if ($id && $record instanceof Record) $record->setId($id);
        return $id;
    }


    /**
     * Get PHP type for MySQL field type
     *
     * @param string $fieldtype
     * @return string
     */
    protected static function getCastType($fieldtype)
    {
        for ($i = 0; $i < 3; $i++) {
            switch ($i) {
                case 0: $key = $fieldtype; break;
                case 1: $key = preg_replace('/\s*\(.+?\)/', '', $fieldtype); break;
                case 2: $key = preg_replace('/\s*\(.+$/', '', $fieldtype); break;
            }

            if (isset(self::$castTypes[$key])) return self::$castTypes[$key];
        }

        trigger_error("Unknown field type '$fieldtype'", E_USER_NOTICE);
        return 'string';
    }
}
