<?php
/**
 * Date: 15/10/22
 * Time: 20:52
 */

namespace Scruit\subsets;


use Scruit\Runnable;

class EnvChanger implements Runnable
{
    private $appRoot = null;
    public function getName()
    {
        return 'change-env';
    }

    public function run($args)
    {
        $this->appRoot = $_SERVER['PWD']. '/app';
        $root = null;
        $env = null;
        if (isset($args['root'])) {
            $root = $args['root'];
        } else {
            $root = $this->appRoot . '/resources';
        }
        if (!isset($args['env'])) {
            throw new \InvalidArgumentException('env is not required.');
        }
        $env = $args['env'];
        if (!is_dir("$root/$env")) {
            throw new \InvalidArgumentException('unknown environment:' . $env);
        }
        print "<<changing env => $env>>\n";
        $path = "$root/$env";
        $dir = opendir($path);
        while ($fn = readdir($dir)){
            if ($fn === '.' || $fn === '..' || is_dir("$path/$fn")) continue;
            print "${path}/${fn} => ${root}/${fn}\n";
            copy("${path}/${fn}", "${root}/${fn}");
        }
    }

    public function doc()
    {?>
control resources directory by environement.

usage:
```
php scruit change-env "env=[envName]"
```
<?php
    }
}