<?php
/**
 * Date: 15/11/09
 * Time: 17:44.
 */

namespace Scruit\subsets\task;


use Scruit\database\Table;
use Scruit\StringUtil;

class DaoGenerator extends BaseTask
{

    public function getTaskName()
    {
        return 'dao';
    }


    /**
     * @param $scheme
     * @return TaskResult
     */
    public function getContent(Table $scheme)
    {
        $selectOne = $scheme->createSelectOne();
        $deleteOne = $scheme->createDeleteOne();
        $className = StringUtil::camelize($scheme->getName()) . 'Dao';
        ob_start();
echo "<?php\n" ?>
namespace <?php echo $this->appName ?>\classes\dao;

class <?php echo $className ?> extends \Mahotora\BaseDao
{
    public function getTableName()
    {
        return '<?php echo $scheme->getName() ?>';
    }
<?php if ($scheme->isAutoIncrement()):?>
    public function save($entity)
    {
        parent::save($entity);
        if (<?php echo implode(' && ', array_map(function($column) {return '!isset($entity->' . $column->getName() . ')';}, $scheme->getPrimaryKeys()))?>) {
            $id = $this->getDatabaseSession()->lastInsertId();
<?php foreach ($scheme->getPrimaryKeys() as $column):?>
            $entity-><?php echo $column->getName()?> = $id;
<?php endforeach;?>
        }
    }
<?php endif;?>

    public function delete($id)
    {
        $this->getDatabaseSession()->executeNoResult(
            "<?php echo $deleteOne['SQL']?>",
            "<?php echo $deleteOne['marker'] ?>",
            is_array($id) ? $id : array ($id)
        );
    }

    public function find($id)
    {
        $result = $this->getDatabaseSession()->find(
            "<?php echo $selectOne['SQL'] ?>",
            "<?php echo $selectOne['marker'] ?>",
            is_array($id) ? $id : array ($id)
        );
        return $result ? $result[0] : null;
    }
}
<?php
        return new TaskResult('app/classes/dao/' . $className . '.php', ob_get_clean());
    }
}