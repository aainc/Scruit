<?php
/**
 * Date: 15/10/05
 * Time: 17:34
 */

namespace Scruit\subsets;


use Scruit\database\Session;
use Scruit\Runnable;

class Loader implements Runnable
{

    private $config = null;
    private $databaseSession = null;
    public function getName()
    {
        return 'load';
    }

    public function run($args)
    {
        $this->config = $args;
        if (!isset($this->config['host'])) throw new \RuntimeException('host is required');
        if (!isset($this->config['user'])) throw new \RuntimeException('user is required');
        if (!isset($this->config['pass'])) throw new \RuntimeException('pass is required');
        if (!isset($this->config['db']))   throw new \RuntimeException('db is required');
        if (!isset($this->config['dir']) || !is_dir($this->config['dir']))  throw new \RuntimeException('dir is required');
        $this->databaseSession = new Session($this->config);
        $dir = opendir($this->config['dir']);
        $queries = array();
        while ($fileName = readdir($dir)){
            if ($fileName === '.' || $fileName === '..') continue;
            $path = realpath($this->config['dir']. "/$fileName");
            if (is_file($path) !== true) {
                echo "$path is not found\n";
                continue;
            }
            $pathInfo = pathinfo($path);
            if ($pathInfo['extension'] !== 'csv') {
                echo "$path is not csv\n";
                continue;
            }
            $table = preg_replace('#^\d+_(.+)\.csv#', '$1', $fileName);
            $queries[$fileName] = array(
                'truncate' => "TRUNCATE TABLE $table",
                'load' => "LOAD DATA LOCAL INFILE '$path' INTO TABLE $table FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES",
            );
        }
        $this->databaseSession->execute("SET foreign_key_checks = 0");
        $keys = array_keys($queries);
        rsort($keys);
        foreach ($keys as $key) {
           $this->databaseSession->execute($queries[$key]['truncate']);
        }
        sort($keys);
        foreach ($keys as $key) {
            $this->databaseSession->execute($queries[$key]['load']);
        }
        $this->databaseSession->execute("SET foreign_key_checks = 1");
    }

    public function doc()
    {?>
loader is bulkload csv file to database scheme.
this command is load data as test data, development environment or integration tests.

usage:
```
php scruit load "dir=[path to test datas]"
```
<?php
    }
}