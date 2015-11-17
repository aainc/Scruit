<?php
/**
 * Date: 15/11/09
 * Time: 18:10.
 */

namespace Scruit\subsets\task;


use Scruit\database\Column;
use Scruit\database\Table;
use Scruit\StringUtil;

class ActionTestGenerator extends BaseTask
{

    public function getTaskName()
    {
        return 'test';
    }

    /**
     * @param Table $scheme
     * @return TaskResult
     */
    public function getContent(Table $scheme)
    {
        ob_start();
        $targetName = StringUtil::camelize($scheme->getName());
        $className = $targetName . 'Test';
        $selectOne = $scheme->createSelectOne();
        $primaryKeys = $scheme->getPrimaryKeys();
print "<?php\n"?>
namespace <?php echo $this->appName ?>\actions;

use \Hoimi\Response\Json;
use \Mahotora\DatabaseSessionImpl;
class <?php echo $className ?> extends \PHPUnit_Framework_TestCase
{
    private $target = null;
    private $dao = null;
    public function setUp ()
    {
        $this->target = new <?php echo $targetName?>();
        $this->dao = \Phake::mock('\<?php echo $this->appName?>\classes\dao\<?php echo $targetName?>');
        $this->target->setDao($this->dao);
    }

<?php if (count($primaryKeys) === 1):?>
    public function testGetWithId ()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        $this->target->setRequest(new \Hoimi\Request(
            array(),
            array('id' => '<?php echo $primaryKeys[0]->dummyValue?>'),
            array()
        ));
        \Phake::when($this->dao)->find('<?php echo $primaryKeys[0]->dummyValue?>')->thenReturn($entity);
        $result = $this->target->get();
        $this->assertSame('<?php echo json_encode((object)$selectOne['dummyHash'])?>', $result->getContent());

    }

    /**
     * @expectedException \Hoimi\Exception\ForbiddenException
     */
    public function testGetNoId ()
    {
        $result = $this->target->get();
    }

    /**
     * @expectedException \Hoimi\Exception\NotFoundException
     */
    public function testGetNotFound()
    {
        $this->target->setRequest(new \Hoimi\Request(
            array(),
            array('id' => '<?php echo $primaryKeys[0]->dummyValue?>'),
            array()
        ));
        $result = $this->target->get();
    }

    public function testPostWithId ()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        $this->target->setRequest(new \Hoimi\Request(
            array(),
            <?php echo $this->dumpHash($selectOne['dummyHash'], 8) ?>,
            array()
        ));
        \Phake::when($this->dao)->find(1)->thenReturn($entity);
        $result = $this->post();
        $this->assertInstanceOf('\Hoimi\Response\Json', $result);
        \Phake::verify($this->dao)->save((object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>);
    }

<?php foreach($scheme->getColumns() as $column): ?>
<?php if ($column->getNotNull()): ?>
    public function testPostNoId ()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        $this->target->setRequest(new \Hoimi\Request(
            array(),
            <?php echo $this->dumpHash($selectOne['dummyHash'], 8) ?>,
            array()
        ));
        \Phake::when($this->dao)->find(1)->thenReturn($entity);
        $result = $this->post();
        $this->assertInstanceOf('\Hoimi\Response\Json', $result);
        \Phake::verify($this->dao)->save((object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>);
    }
<?php endif; ?>
<?php endforeach; ?>
<?php else:?>
    // TODO: too many primary keys. Generator can't generate this action from Table.
<?php endif;?>
}
<?php
        return new TaskResult('tests/actions/' . $className . '.php', ob_get_clean());
    }
}