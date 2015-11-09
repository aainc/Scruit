<?php
print "<?php\n";
foreach (getDependencies() as $file) {
    $path =  __DIR__ . '/' . $file;
    $command = "php -w $path";
    //print preg_replace ('#^<\?php#', '' , `$command`, 1) . "\n";
    print preg_replace ('#^<\?php#', '' , file_get_contents($path), 1) . "\n";
}
function getDependencies () {
    return array(
        'Runnable.php',
        'Runner.php',
        'StringUtil.php',
        'database/Column.php',
        'database/Diff.php',
        'database/Table.php',
        'database/Session.php',
        'subsets/Generator.php',
        'subsets/task/BaseTask.php',
        'subsets/task/Generatable.php',
        'subsets/task/ActionGenerator.php',
        'subsets/task/ComposerJsonGenerator.php',
        // 'subsets/task/ActionTestGenerator.php',
        'subsets/task/BootStrapGenerator.php',
        'subsets/task/BuildXMLGenerator.php',
        'subsets/task/ConfigGenerator.php',
        'subsets/task/DaoGenerator.php',
        'subsets/task/DaoTestGenerator.php',
        'subsets/task/GitIgnoreGenerator.php',
        'subsets/task/IndexPhpGenerator.php',
        'subsets/task/ScruitGenerator.php',
        'subsets/task/TaskResult.php',
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
system("cd $root/src && curl -sS https://getcomposer.org/installer | php");
system("cd $root/src && php composer.phar install");
unlink(__FILE__);
