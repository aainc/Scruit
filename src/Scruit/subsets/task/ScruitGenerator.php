<?php
/**
 * Date: 15/11/09
 * Time: 18:00.
 */

namespace Scruit\subsets\task;


use Scruit\database\Table;

class ScruitGenerator implements Generatable
{

    public function getTaskName()
    {
        return 'scruit';
    }

    public function getContents(array $schemes)
    {
        ob_start()?>
#!<?php echo `which php`?>
<?php echo "<?php\n"?>
if (count($argv) < 2 ) die ('no command');
$name = null;
$options = null;
array_shift($argv);
$name = array_shift($argv);
$argv && $options = array_shift($argv);
$baseDir = __DIR__;
if (is_file( $baseDir . '/.scruit')) {
    $dictionary = require $baseDir . '/.scruit';
    isset($dictionary[$name]) && $name = $dictionary[$name];
}
if ($name === 'ls') {
    print "<<origial commands>>\n";
    foreach ($dictionary as $key => $val) {
        print "$key\n";
    }
    print "\nif you type 'man=true' argument, scruit show you a subcommand manual. php scruit [subcommand] 'man=true'\n\n";
}
if ($name === 'load' && $options === null && is_file($baseDir . '/src/app/resources/database.php')) {
    $database = require $baseDir . '/src/app/resources/database.php';
    $options  = "host=" . $database['host'] . ' ';
    $options .= "user=" . $database['user'] . ' ';
    $options .= "pass=" . $database['pass'] . ' ';
    $options .= "db="   . $database['database'] . ' ';
    $options .= "dir=" . $baseDir . '/datas';
}

if ($name === 'migrate' && $options === null && is_file($baseDir . '/src/app/resources/database.php')) {
    $database = require $baseDir . '/src/app/resources/database.php';
    if (is_file($baseDir . '/datas/create_table.sql')) {
        if (!isset($database['workScheme'])) $database['workScheme'] = 'migrate';
        $options  = 'targetScheme=mysql://' . $database['user'] . ':' . $database['pass'] . '@' . $database['host'] . '/' . $database['database'] .' ';
        $options .= 'workScheme=mysql://'   . $database['user'] . ':' . $database['pass'] . '@' . $database['host'] . '/' . $database['workScheme'] . ' ';
        $options .= 'createTable=' . $baseDir . '/datas/create_table.sql';
    }
}
$command = "php $baseDir/src/vendor/aainc/scruit/src/Scruit/Runner.php -n=" . escapeshellarg($name);
$command .= " --bootstrap=" . escapeshellarg(__DIR__ . '/src/app/bootstrap.php');
if ($options) $command .= ' --optional=' . escapeshellarg($options);
exit(system($command) === false ? 1 : 0);
<?php
        return array(new TaskResult('scruit', ob_get_clean()));
    }
}