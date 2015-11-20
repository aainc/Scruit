<?php
/**
 * Date: 15/11/09
 * Time: 18:09.
 */

namespace Scruit\subsets\task;



class ConfigGenerator implements Generatable
{
    private $config = null;
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getTaskName()
    {
        return 'config';
    }

    public function getContents(array $schemes)
    {
        return array(
            $this->getConfigPhp(),
            $this->getDatabasePhp(),
            $this->getSessionPhp(),
            $this->getLogPhp(),
        );
   }

    public function getConfigPhp()
    {
        ob_start();
echo "<?php\n" ?>
return new \Hoimi\Config(__FILE__);
<?php
        return new TaskResult('app/resources/config.php', ob_get_clean());
    }

    public function getDatabasePhp()
    {
        ob_start();
echo "<?php\n"?>
return array(
    'host' => '<?php echo $this->config['host']?>',
    'user' => '<?php echo $this->config['user']?>',
    'pass' => '<?php echo $this->config['pass']?>',
    'database' => '<?php echo $this->config['db']?>',
    // if you use a scruit migration function, create empty database and set it to this variable.
    'workScheme' => null,
);
<?php
        return new TaskResult('app/resources/database.php', ob_get_clean());
    }

    public function getSessionPhp()
    {
        ob_start();
print "<?php\n"?>
return array (
    // 'keyName' => 'hoge',
    // 'maxLifeTime' => 1000000 ,
    // 'driver' => 'DB',
    // 'database' => array(
    //     'host' => 'localhost',
    //     'user' => 'user',
    //     'pass' => 'pass',
    //     'database' => 'databaseName',
    // ),
    );
<?php
        return new TaskResult('app/resources/session.php', ob_get_clean());
    }

    public function getLogPhp()
    {
        ob_start();
print "<?php\n"?>
return array (
    'directory' => '/tmp',
    'level' => \Monolog\Logger::INFO,
);
<?php
        return new TaskResult('app/resources/log.php', ob_get_clean());
    }
}
