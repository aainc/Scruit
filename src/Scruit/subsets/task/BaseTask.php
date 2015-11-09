<?php
/**
 * Date: 15/11/09
 * Time: 16:47.
 */

namespace Scruit\subsets\task;


use Scruit\database\Table;

abstract class BaseTask implements Generatable
{
    protected $appName = null;

    public function __construct ($appName)
    {
        $this->appName = $appName;
    }


    /**
     * @param Table $scheme
     * @return TaskResult
     */
    public abstract function getContent(Table $scheme);

    /**
     * @param Table[] $schemes
     * @return TaskResult[]
     */
    public function getContents (array $schemes)
    {
        $results = array();
        foreach ($schemes as $scheme) {
            $results[] =  $this->getContent($scheme);
        }
        return $results;
    }

    public function dumpHash($arr, $level, $trim = true)
    {ob_start();?>
<?php echo str_repeat(' ', $level)?>array(
<?php foreach($arr as $key => $value):?>
<?php if (gettype($value) === 'string'):?>
<?php echo str_repeat(' ', $level + 4)?>"<?php echo str_replace('"', '\\"', $key)?>" => "<?php echo str_replace('"', '\\"', $key)?>",
<?php else:?>
<?php echo str_repeat(' ', $level + 4)?>"<?php echo str_replace('"', '\\"', $key)?>" => <?php echo $value?>,
<?php endif;?>
<?php endforeach;?>
<?php echo str_repeat(' ', $level)?>)
<?php return $trim ? trim(ob_get_clean()) : ob_get_clean();
    }

    public function dumpArray($arr, $level, $trim = true)
    {ob_start();?>
<?php echo str_repeat(' ', $level)?>array(
<?php foreach($arr as $value):?>
<?php if (gettype($value) === 'string'):?>
<?php echo str_repeat(' ', $level + 4)?>"<?php echo str_replace('"', '\\"', $value)?>",
<?php else:?>
<?php echo str_repeat(' ', $level + 4)?><?php echo $value?>,
<?php endif;?>
<?php endforeach;?>
<?php echo str_repeat(' ', $level)?>)
<?php return $trim ? trim(ob_get_clean()) : ob_get_clean();
    }
}
