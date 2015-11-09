<?php
namespace Scruit;
class Runner
{
    private $commands = array();
    public function __construct () {
        foreach (glob(__DIR__ . '/subsets/*') as $fileName) {
            require_once realpath($fileName);
            $this->add('Scruit\subsets\\' . str_replace('.php', '', basename($fileName)));
        }
    }

    public function add ($className)
    {
        $obj = new $className;
        if ($obj instanceof Runnable) {
            $this->commands[$obj->getName()] = $obj;
        }
    }

    private static $instance = null;
    public static function getInstance ()
    {
        if (self::$instance === null) {
            self::$instance = new Runner();
        }
        return self::$instance;
    }

    public function run($options)
    {
        if (isset($options['bootstrap'])) {
            require_once $options['bootstrap'];
        }
        $result = null;
        $args = isset($options['optional']) ? StringUtil::parseConfig($options['optional']) : array();
        if (!isset($options['n']) || $options['n'] === 'ls') {
            $result = "<<scruit subsets>>\n";
            foreach ($this->commands as $key => $val) {
                $result .= "$key\n";
            }
        } elseif (isset($this->commands[$options['n']]) || class_exists($options['n'])) {
            $command = isset($this->commands[$options['n']]) ? $this->commands[$options['n']] : new $options['n'];
            if (in_array('Scruit\Runnable', class_implements($command))) {
                if (isset($args['man'])) {
                    $command->doc();
                } else {
                    $result = $command->run($args);
                }
            } else {
                $result = $options['n'] . ' is not runner.';
            }
        }  else {
            $result = $options['n'] . ' is not exists.';
        }
        return $result;
    }
}
if (php_sapi_name() === 'cli' && basename($_SERVER['SCRIPT_NAME']) === 'Runner.php' && isset($argv) && count($argv)) {
    require __DIR__ . '/bootstrap.php';
    $options = getopt('n::', array ('optional::', 'bootstrap::'));
    $result = 0;
    try {
        echo Runner::getInstance()->run($options) . "\n";
    } catch (\Exception $e) {
        error_log("<< " . get_class($e) . " >>\n");
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
        $result = 1;
    }
    exit($result);
}
