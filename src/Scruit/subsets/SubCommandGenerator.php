<?php
/**
 * Date: 15/10/19
 * Time: 10:12
 */

namespace Scruit\subsets;


use Scruit\Runnable;

class SubCommandGenerator  implements Runnable
{

    public function getName()
    {
        return 'gen-dot-scruit';
    }

    public function run($args)
    {
        if (!isset($args['dir']) || !is_dir($args['dir'] )) {
            throw new \InvalidArgumentException('need dir');
        }
        $root = getcwd();
        $dotScruitPath = "$root/.scruit";
        $handle = opendir($args['dir']);
        $commands = null;
        if (is_file($dotScruitPath))  $commands = require $dotScruitPath;
        else                          $commands = array();
        while ($fn = readdir($handle)) {
            if ($fn === '.' || $fn === '..' || substr($fn, -4) !== '.php') continue;
            $path = "${args['dir']}/$fn";
            require_once $path;
            $info = pathinfo($path);
            $className = null;
            if (preg_match('#namespace +(\S+);#', file_get_contents($path), $tmp)) {
                $className = $tmp[1] . '\\' . $info['filename'];
            } else {
                $className = $info['filename'];
            }
            $clazz = new \ReflectionClass($className);
            if ($clazz->implementsInterface('Scruit\Runnable') && !$clazz->isAbstract() && !$clazz->isInterface() && !$clazz->isTrait())  {
                $obj = $clazz->newInstance();
                $commands[$obj->getName()] = $clazz->getName();
            }
        }
        file_put_contents($dotScruitPath, "<?php\n" . 'return ' . var_export($commands, true) . ';');
        print "generated:$dotScruitPath\n";
    }

    public function doc()
    {?>
this command is traverse target directory and generate ".scruit"

usage:
```
php scruit migrate "dir=[A path to directory of commands]"
```
<?php
    }
}