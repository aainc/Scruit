<?php
/**
 * ColumnTest.php@gadget_enquete
 * User: ishidatakeshi
 * Date: 15/09/14
 * Time: 18:38
 */

namespace Scruit\database;


class ColumnTest extends \PHPUnit_Framework_TestCase
{
    private $stringTarget = null;
    private $integerTarget = null;
    private $blobTarget = null;
    private $doubleTarget = null;

    protected function setUp()
    {
        $this->integerTarget = new Column(array(
            'name' => 'id',
            'dataType' => 'bigint(100)' ,
            'primary' => true,
            'autoIncrement' => true,
            'notNull' => true,
            'default' => null,
        ));
        $this->stringTarget = new Column(array(
            'name' => 'col1',
            'dataType' => 'VARCHAR(255)' ,
            'primary' => false,
            'autoIncrement' => false,
            'notNull' => true,
            'default' => null,
        ));
        $this->doubleTarget = new Column(array(
            'name' => 'col2',
            'dataType' => 'double' ,
            'primary' => false,
            'autoIncrement' => false,
            'notNull' => false,
            'default' => null,
        ));

        $this->blobTarget = new Column(array(
            'name' => 'col3',
            'dataType' => 'blob' ,
            'primary' => false,
            'autoIncrement' => false,
            'notNull' => false,
            'default' => null,
        ));
    }

    public function testInteger ()
    {
        $this->assertSame('i', $this->integerTarget->marker());
        $this->integerTarget->setDataType('int');
        $this->assertSame('i', $this->integerTarget->marker());
    }

    public function testString ()
    {
        $this->assertSame('s', $this->stringTarget->marker());

        $this->stringTarget->setDataType('char');
        $this->assertSame('s', $this->stringTarget->marker());

        $this->stringTarget->setDataType('datetime');
        $this->assertSame('s', $this->stringTarget->marker());
    }

    public function testDouble ()
    {
        $this->assertSame('d', $this->doubleTarget->marker());
        $this->doubleTarget->setDataType('float');
        $this->assertSame('d', $this->doubleTarget->marker());
    }

    public function testBlob ()
    {
        $this->assertSame('b', $this->blobTarget->marker());
        $this->doubleTarget->setDataType('longblob');
        $this->assertSame('b', $this->blobTarget->marker());
    }

}