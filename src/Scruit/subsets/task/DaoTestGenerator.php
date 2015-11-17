<?php
/**
 * Date: 15/11/09
 * Time: 17:06.
 */

namespace Scruit\subsets\task;


use Scruit\database\Table;
use Scruit\StringUtil;

class DaoTestGenerator extends BaseTask
{

    public function getTaskName()
    {
        return 'test';
    }

    public function getContents (array $schemes)
    {
        $result = parent::getContents($schemes);
        $result[] = $this->getPhpUnitXMLContent();
        $result[] = $this->getBootstrapContent();
        return $result;
    }

    /**
     * @param $scheme
     * @return TaskResult
     */
    public function getContent(Table $scheme)
    {
        $selectOne = $scheme->createSelectOne(); ob_start();
        $className = StringUtil::camelize($scheme->getName()) . 'DaoTest';
echo "<?php\n" ?>
namespace <?php echo $this->appName ?>\classes\dao;

class <?php echo $className ?> extends \PHPUnit_Framework_TestCase
{
    private $target = null;
    private $databaseSession = null;

    public function setUp()
    {
        $this->databaseSession = \Phake::mock('Mahotora\DatabaseSessionImpl');
        $this->target = new \<?php echo $this->appName ?>\classes\dao\<?php echo StringUtil::camelize($scheme->getName()) ?>Dao($this->databaseSession);
    }

    public function testFind()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        \Phake::when($this->databaseSession)->find(
            "<?php echo $selectOne['SQL']?>",
            "<?php echo $selectOne['marker']?>",
            <?php echo $this->dumpArray($selectOne['dummyKeys'], 12)?>
        )->thenReturn(array($entity));
        $result = $this->target->find(<?php echo $this->dumpArray($selectOne['dummyKeys'], 8)?>);
        $this->assertSame($entity, $result);
    }

    public function testFindNoResult()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        \Phake::when($this->databaseSession)->find(
            "<?php echo $selectOne['SQL']?>",
            "<?php echo $selectOne['marker']?>",
            <?php echo $this->dumpArray($selectOne['dummyKeys'], 12)?>
        )->thenReturn(array());
        $result = $this->target->find(<?php echo $this->dumpArray($selectOne['dummyKeys'], 8)?>);
        $this->assertSame(null, $result);
    }
}
<?php
        return new TaskResult('tests/classes/dao/' . $className . '.php', ob_get_clean());

    }

    public function getBootstrapContent()
    {
        ob_start();
print "<?php\n"?>
require_once __DIR__ . '/../vendor/autoload.php';
<?php
        return new TaskResult("tests/bootstrap.php", ob_get_clean());
    }

    public function getPhpUnitXMLContent()
    { ob_start();?>
<phpunit
    bootstrap="./tests/bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    verbose="true"
    stopOnFailure="true"
    processIsolation="false"
    backupGlobals="false"
    syntaxCheck="true"
    >
    <testsuite name="<?php echo $this->appName?>">
        <directory>./tests</directory>
    </testsuite>
</phpunit>
<?php
        return new TaskResult('phpunit.xml', ob_get_clean());
    }
}