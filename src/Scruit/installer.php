<?php
print "<?php\n";

foreach (getDependencies() as $file) {
    if (isset($argv[1]) && $argv[1] === 'local') {
        $file = str_replace('https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit', __DIR__, $file);
    }
    print preg_replace ('#^<\?php#', '' , file_get_contents($file), 1) . "\n";
}
function getDependencies () {
    return array(
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/Runnable.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/Runner.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/StringUtil.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/database/Column.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/database/Diff.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/database/Table.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/database/Session.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/Generator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/BaseTask.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/Generatable.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/ActionGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/ComposerJsonGenerator.php',
        // 'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/ActionTestGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/BootStrapGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/BuildXMLGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/ConfigGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/DaoGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/DaoTestGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/GitIgnoreGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/IndexPhpGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/ScruitGenerator.php',
        'https://raw.githubusercontent.com/aainc/Scruit/master/src/Scruit/subsets/task/TaskResult.php',
    );
}
?>
$params  = array();
print '┌-------------------------------┐' . "\n";
print '|        Scruit Generator       |' . "\n";
print '└-------------------------------┘' . "\n";
print "\n\n";
print "plz input some variables.\n";
print "[app] is a this applications'name.\n";
print "[host] is a hostname of database server.\n";
print "[db] is a database name.\n";
print "[user] is a username of a database server.\n";
print "[pass] is a password of a database server.\n";
print "\n\n";

foreach(array('app', 'host', 'db', 'user', 'pass') as $key) {
    print "$key:";
    $params[] = $key . '=' . trim(fgets(STDIN));
}
$runner = new \Scruit\Runner();
$runner->add('\Scruit\subsets\Generator');
$runner->run(array('n' => 'init', 'optional' => implode(' ', $params)));
$root = $_SERVER['PWD'];
system("cd $root && curl -sS https://getcomposer.org/installer | php");
system("cd $root && php composer.phar install");
unlink(__FILE__);
