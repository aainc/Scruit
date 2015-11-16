<?php
/**
 * Date: 15/11/09
 * Time: 17:50.
 */

namespace Scruit\subsets\task;


use Scruit\database\Table;
use Scruit\StringUtil;

class ActionGenerator extends BaseTask
{

    public function getTaskName()
    {
        return 'action';
    }

    /**
     * @param Table $scheme
     * @return TaskResult
     */
    public function getContent(Table $scheme)
    {
        $className = StringUtil::camelize($scheme->getName());
        ob_start();
echo "<?php\n" ?>
namespace <?php echo $this->appName ?>\actions;

use \Hoimi\Response\Json;
use \Mahotora\DatabaseSessionImpl;
class <?php echo $className ?> extends \Hoimi\BaseAction
{
<?php if (count($scheme->getPrimaryKeys()) === 1):?>
    private $dao = null;
    public function get()
    {
        $id = $this->getRequest()->get('id');
        $response = null;
        if ($id) {
            $data = $this->getDao()->find($id);
            if ($data) {
                $response = new \Hoimi\Response\Json($data);
            } else {
                throw new \Hoimi\Exception\NotFoundException();
            }
        } else {
            throw new \Hoimi\Exception\ForbiddenException();
        }
        return $response;
    }

    public function post()
    {
        $request = $this->getRequest();
        $validationResult = \Hoimi\Validator::validate($request, array(
<?php foreach ($scheme->getColumns() as $column):?>
            '<?php echo $column->getName()?>' => <?php echo preg_replace('#\s*,\s*\)#', ')', preg_replace('#\(\s*#', '(', str_replace("\n", "", var_export($column->validatorDefinition(), true))))?>,
<?php endforeach;?>
        ));
        if ($validationResult) {
            throw new \Hoimi\Exception\ValidationException($validationResult);
        }

        $id = $request->get('id');
        $response = null;
        if ($id) {
            $data = $this->getDao()->find($id);
        }
        if (!$data) {
            $data = new \stdClass();
        }
<?php foreach ($scheme->getColumns() as $column):?>
        $data-><?php echo $column->getName()?> = $request->get('<?php echo $column->getName()?>');
<?php endforeach;?>
        $this->getDao()->save($data);
        $response = new \Hoimi\Response\Json($data);
        return $response;
    }
<?php else:?>

// too many primary keys. we can't generate action.

<?php endif;?>
    public function setDao($dao)
    {
        $this->dao = $dao;
    }

    public function getDao()
    {
        if ($this->dao === null) {
            $this->dao = new \<?php echo StringUtil::camelize($this->appName)?>\classes\dao\<?php echo StringUtil::camelize($scheme->getName())?>Dao(
                DatabaseSessionFactory::build($this->getConfig()->get('database'))
            );
        }
        return $this->dao;
    }
}
<?php
       return new TaskResult('src/app/actions/' . $className . '.php', ob_get_clean());
    }
}