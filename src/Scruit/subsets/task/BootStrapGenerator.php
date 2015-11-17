<?php
/**
 * Date: 15/11/09
 * Time: 17:53.
 */

namespace Scruit\subsets\task;


use Scruit\StringUtil;

class BootStrapGenerator implements Generatable
{

    private $appName = null;
    public function __construct($appName)
    {
        $this->appName = $appName;
    }
    public function getTaskName()
    {
        return 'bootstrap';
    }

    public function getContents(array $schemes)
    {
        ob_start();
echo "<?php\n" ?>
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new \Exception("STRICT: $errno $errstr $errfile $errline");
});
require realpath(__DIR__ . '/../vendor/autoload.php');
return \Hoimi\Router::getInstance()->setRoutes(array(
    '/batch_request' => 'Hoimi\BatchRequest',
<?php foreach ($schemes as $scheme):?>
    '/<?php echo $scheme->getName() ?>' => '<?php echo $this->appName ?>\actions\<?php echo StringUtil::camelize($scheme->getName())?>',
<?php endforeach;?>
));
<?php
        return array(new TaskResult('app/bootstrap.php', ob_get_clean()));
    }
}