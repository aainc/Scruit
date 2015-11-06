<?php
/**
 * TableTest.php@gadget_enquete
 * User: ishidatakeshi
 * Date: 15/09/14
 * Time: 18:38
 */

namespace Scruit\database;


class TableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Table
     */
    private $target = null;

    protected function setUp()
    {
        $this->target = new Table('tableName');
        $this->target->addColumn(new Column(array(
             'name' => 'id',
             'dataType' => 'bigint(100)' ,
             'primary' => true,
             'autoIncrement' => true,
             'notNull' => true,
             'default' => null,
        )));
        $this->target->addColumn(new Column(array(
             'name' => 'col1',
             'dataType' => 'VARCHAR(255)' ,
             'primary' => false,
             'autoIncrement' => false,
             'notNull' => true,
             'default' => null,
        )));
        $this->target->addColumn(new Column(array(
             'name' => 'col2',
             'dataType' => 'double' ,
             'primary' => false,
             'autoIncrement' => false,
             'notNull' => false,
             'default' => null,
        )));
    }

    public function testSelectOne ()
    {
        $result = $this->target->createSelectOne();
        $this->assertSame('SELECT * FROM tableName WHERE id = ?', $result['SQL']);
        $this->assertSame('i', $result['marker']);
        $this->assertSame(array('id'), $result['columns']);
    }

    public function testInsert ()
    {
        $result = $this->target->createInsert();
        $this->assertSame('INSERT INTO tableName (col1,col2) VALUES (?,?)', $result['SQL']);
        $this->assertSame('sd', $result['marker']);
        $this->assertSame(array('col1', 'col2'), $result['columns']);
    }

    public function testSave ()
    {
        $result = $this->target->createSave();
        $this->assertSame('REPLACE INTO tableName (id,col1,col2) VALUES (?,?,?)', $result['SQL']);
        $this->assertSame('isd', $result['marker']);
        $this->assertSame(array('id', 'col1', 'col2'), $result['columns']);
    }

    public function testUpdate ()
    {
        $result = $this->target->createUpdate();
        $this->assertSame('UPDATE tableName SET col1 = ?, col2 = ? WHERE id = ?', $result['SQL']);
        $this->assertSame('sdi', $result['marker']);
        $this->assertSame(array('col1', 'col2', 'id'), $result['columns']);
    }
}