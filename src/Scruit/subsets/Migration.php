<?php
/**
 * Date: 15/10/02
 * Time: 18:40
 */

namespace Scruit\subsets;


use Scruit\database\Analyzer;
use Scruit\database\Diff;
use Scruit\database\Session;
use Scruit\Runnable;

class Migration implements Runnable
{
    private $config = array();
    public function getName()
    {
        return 'migrate';
    }

    public function run($args)
    {
        $this->config = $args;
        if (!isset($this->config['createTable']))  throw new \RuntimeException('createTable  is not required');
        if (!isset($this->config['targetScheme'])) throw new \RuntimeException('targetScheme is not required');
        if (!isset($this->config['workScheme']))   throw new \RuntimeException('workScheme   is not required');
        $database = new Session($this->config['targetScheme']);
        $diff = new Diff(
            new Session($this->config['workScheme'], $this->config['createTable']),
            $database
        );
        if (isset($args['dry-run'])) {
            print $diff  . "\n";
        } else {
            if ($diff->toScript()) {
                $database->executeMulti($diff->toScript());
            }
        }
    }

    public function doc()
    {?>
migrate is database migration command.

usage:
```
php scruit migrate "createTable=[path to CreateTableSQL] workScheme=[empty database name]"
```
<?php  }
}