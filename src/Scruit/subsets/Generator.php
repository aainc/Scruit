<?php
namespace Scruit\subsets;

use Scruit\database\Session;
use Scruit\subsets\task\ActionGenerator;
use Scruit\subsets\task\BootStrapGenerator;
use Scruit\subsets\task\BuildXMLGenerator;
use Scruit\subsets\task\ComposerJsonGenerator;
use Scruit\subsets\task\ConfigGenerator;
use Scruit\subsets\task\DaoGenerator;
use Scruit\subsets\task\DaoTestGenerator;
use Scruit\subsets\task\Generatable;
use Scruit\subsets\task\GitIgnoreGenerator;
use Scruit\subsets\task\IndexPhpGenerator;
use Scruit\subsets\task\ScruitGenerator;
use Scruit\subsets\task\TaskResult;

class Generator implements \Scruit\Runnable
{
    private $session = null;
    private $appName = null;
    private $root = null;
    private $force = null;
    private $config = array();

    public function getName()
    {
        return 'init';
    }

    public function doc()
    {?>
        "init" is create application's directory and generate scaff folding files by database scheme.

        usage:
        ```
        php [path to scruit/Runner.php] -n=init -options="app=[appName] host=[databaseHost] user=[databaseUser] pass=[databaseUserPassword] db=[databaseName]"
        ```
        options-optional:
        force: [true/false] if exists file, overwrite it.
        mode: [all|scruit|bootstrap|config|buildxml|composer|gitignore|indexphp|actions|dao|test] limit task's scope.
    <?php
    }

    public function run($args)
    {
        $this->config = $args;
        if (!isset($this->config['app']))  throw new \RuntimeException('app is required');
        if (!isset($this->config['host'])) throw new \RuntimeException('host is required');
        if (!isset($this->config['user'])) throw new \RuntimeException('user is required');
        if (!isset($this->config['pass'])) throw new \RuntimeException('pass is required');
        if (!isset($this->config['db']))   throw new \RuntimeException('db is required');

        $this->force    = isset($this->config['force']) && strtolower($this->config['force']) != 'false';
        $this->root     = $_SERVER['PWD'];
        $this->appName  = $this->config['app'];
        $this->session = new Session($this->config);
        $schemes  = $this->session->getScheme();
        $mode     = isset($this->config['mode'])  ? strtolower($this->config['mode']) : 'all';

        foreach ($this->getTasks() as $generatable) {
            if ($mode !== 'all' && $generatable->getTaskName() !== $mode) continue;
            print '<< ' .  $generatable->getTaskName() . ' is running >>' . "\n";
            foreach ($generatable->getContents($schemes) as $result) {
                print $result->getFileName() . ' is ' . ($this->gracefulSave($result) ? 'saved' : 'skiped') . "\n";
            }
            print "\n";
        }
        if (!is_dir($this->root . '/datas')) {
            mkdir($this->root . '/datas');
        }
        system("mysqldump -u " . $this->config['user'] . " -p". $this->config['pass'] . " -h " . $this->config['host'] . " --database " . $this->config['db'] . " --no-data > $this->root/datas/create_table.sql");
    }


    /**
     *
     * @return Generatable[]
     */
    public function getTasks()
    {
        return array(
            new ComposerJsonGenerator($this->appName),
            new ScruitGenerator(),
            new GitIgnoreGenerator(),
            new IndexPhpGenerator(),
            new BootStrapGenerator($this->appName),
            new BuildXMLGenerator($this->appName),
            new ConfigGenerator($this->config),
            new DaoGenerator($this->appName),
            new DaoTestGenerator($this->appName),
        );
    }

    public function gracefulSave (TaskResult $result)
    {
        $path = $this->root . '/' . $result->getFileName();
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        if ($this->force || !is_file($path)){
            return file_put_contents($path, $result->getContent());
        } else {
            return false;
        }
    }
}
