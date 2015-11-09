<?php
/**
 * Date: 15/11/09
 * Time: 17:57.
 */

namespace Scruit\subsets\task;

class IndexPhpGenerator implements Generatable
{
    public function getTaskName()
    {
        return 'index';
    }

    public function getContents(array $schemes)
    {
           ob_start();
echo "<?php\n"?>
$router = require realpath(__DIR__ . '/../src/app/bootstrap.php');
$config = require realpath(__DIR__ . '/../src/app/resources/config.php');
$request = new \Hoimi\Request($_SERVER, $_REQUEST);
$response = null;
try {
    list($action, $method) = $router->run($request);
    $action->setConfig($config);
    $action->setRequest($request);
    if ($action->useSessionVariables()) {
        $session = \Hoimi\Session::getInstance($request, $config->get('session'));
        $action->setSession($session);
        $session->start();
        $response = $action->$method();
        $session->flush();
    } else {
        $response = $action->$method();
    }
} catch (\Hoimi\Exception $e) {
    $response = $e->buildResponse();
} catch (\Exception $e) {
    $response = new \Hoimi\Response\Error($e);
}
foreach ($response->getHeaders() as $header) {
    header($header);
}
echo $response->getContent();
<?php
        return array(new TaskResult('docroot/index.php', ob_get_clean()));
    }
}