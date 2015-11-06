<?php
namespace Scruit\subsets;

use Scruit\database\Analyzer;
use Scruit\database\Session;
use Scruit\StringUtil;

class Generator implements \Scruit\Runnable
{
    private $session = null;
    private $appName = null;
    private $appRoot = null;
    private $testRoot = null;
    private $root = null;
    private $force = null;
    private $schemes = array();
    private $config = array();
    private $mode = null;

    public function getName()
    {
        return 'init';
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
        $this->mode     = isset($this->config['mode'])  ? strtolower($this->config['mode']) : 'all';
        $this->root     = $_SERVER['PWD'];
        $this->appName  = $this->config['app'];
        $this->session = new Session($this->config);
        $this->appRoot  = $this->root . '/src/app';
        $this->testRoot  = $this->root . '/src/tests';
        $this->schemes  = $this->session->getScheme();
        foreach ($this->directory() as $path) {
            if (!is_dir($path)) {
                mkdir($path);
                print "$path is created\n";
            }
        }
        if ($this->mode === 'all' || $this->mode === 'scruit')    $this->scruit();
        if ($this->mode === 'all' || $this->mode === 'bootstrap') $this->bootstrap();
        if ($this->mode === 'all' || $this->mode === 'config')    $this->config();
        if ($this->mode === 'all' || $this->mode === 'buildxml')  $this->buildXML();
        if ($this->mode === 'all' || $this->mode === 'composer')  $this->composerJSON();
        if ($this->mode === 'all' || $this->mode === 'gitignore') $this->gitIgnore();
        if ($this->mode === 'all' || $this->mode === 'indexphp')  $this->indexPhp();
        if ($this->mode === 'all' || $this->mode === 'actions')   $this->actions();
        if ($this->mode === 'all' || $this->mode === 'dao')       $this->dao();
        if ($this->mode === 'all' || $this->mode === 'test')      $this->test();
    }

    public function scruit ()
    {

        $path = $this->root . '/scruit';
        ob_start()?>
#!<?php echo `which php`?>
<?php echo '<?php'?>
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
    if (is_file($baseDir . '/src/app/resources/create_table.sql')) {
        if (!isset($database['workScheme'])) $database['workScheme'] = 'migrate';
        $options  = 'targetScheme=mysql://' . $database['user'] . ':' . $database['pass'] . '@' . $database['host'] . '/' . $database['database'] .' ';
        $options .= 'workScheme=mysql://'   . $database['user'] . ':' . $database['pass'] . '@' . $database['host'] . '/' . $database['workScheme'] . ' ';
        $options .= 'createTable=' . $baseDir . '/src/app/resources/create_table.sql';
    }
}
$command = "php $baseDir/src/vendor/aainc/scruit/src/Scruit/Runner.php -n=" . escapeshellarg($name);
$command .= " --bootstrap=" . escapeshellarg(__DIR__ . '/src/app/bootstrap.php');
if ($options) $command .= ' --optional=' . escapeshellarg($options);
exit(system($command) === false ? 1 : 0);
<?php $this->gracefulSave($path, ob_get_clean());
    }

    public function directory()
    {
        return array (
            $this->root . '/assets',
            $this->root . '/docroot',
            $this->root . '/docs',
            $this->root . '/src',
            $this->root . '/src/tests',
            $this->root . '/src/tests/actions',
            $this->root . '/src/tests/classes/dao',
            $this->root . '/src/app',
            $this->root . '/src/app/actions',
            $this->root . '/src/app/classes',
            $this->root . '/src/app/classes/dao',
            $this->root . '/src/app/classes/toolkit',
            $this->root . '/src/app/resources',
        );
    }

    public function indexPhp()
    {
        $path = $this->root . '/docroot/index.php';
        ob_start();echo '<?php'?>

$router = require realpath(__DIR__ . '/../src/app/bootstrap.php');
$config = require realpath(__DIR__ . '/../src/app/resources/config.php');
$request = new \Hoimi\Request($_SERVER, $_REQUEST);
$response = null;
try {
    list($action, $method) = $router->run($request);
    $action->setConfig($config);
    $action->setRequest($request);
    if ($action->useSessionVariables()) {
        $session = \Hoimi\Session::getInstance($request, $config->get('session'));
        $action->setSession($session);
        $session->start();
        $response = $action->$method();
        $session->flush();
    } else {
        $response = $action->$method();
    }
} catch (\Hoimi\Exception $e) {
    $response = $e->buildResponse();
} catch (\Exception $e) {
    $response = new \Hoimi\Response\Error($e);
}
foreach ($response->getHeaders() as $header) {
    header($header);
}
echo $response->getContent();
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
    }

    public function gitIgnore()
    {
        $path = $this->root . '/.gitignore';
        ob_start();?>
VagrantFile
.vagrant
.idea
**/vendor
**/*.bak
**/*.bk
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
    }
    public function composerJSON()
    {
        $path = $this->appRoot . '/../composer.json';
        ob_start();?>
{
    "name": "<?php echo $this->appName?>",
    "autoload" : {
        "psr-4" : {
            "<?php echo StringUtil::camelize($this->appName)?>\\" : "app"
        }
    },
    "packages": {
    },
    "require-dev": {
        "phing/phing": "2.*",
        "phake/phake": "2.*",
        "PHPUnit/phpunit": "*",
        "phpdocumentor/phpdocumentor" : "*",
        "sebastian/phpcpd" : "*",
        "phpmd/phpmd" : "*",
        "pdepend/pdepend" : "*",
        "phploc/phploc" : "*",
        "squizlabs/php_codesniffer": "2.*",
        "fabpot/php-cs-fixer": "*"
    },
    "require": {
        "monolog/monolog": "@stable"
    }
}
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
    }

    public function buildXML()
    {
        $path = $this->appRoot . '/../build.xml';
        ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>'?>

<project name="<?php echo htmlspecialchars($this->appName, ENT_QUOTES)?>" default="build">
    <property name="basedir" value="." />
    <property name="test.dir" value="${basedir}/tests" />
    <property name="reports.dir" value="${basedir}/reports" />
    <property name="vendor.dir" value="${basedir}/vendor" />
    <property name="app.dir" value="${basedir}/app" />
    <property name="test.report.dir" value="${reports.dir}/test" />
    <property name="doc.dir" value="${reports.dir}/doc" />
    <target name="build" depends="prepare, phpcpd, phpmd, phpcs, phpunit, phpdoc"/>

    <target name="vendor_update" description="update libraries via composer">
        <if>
            <available file="${vendor.dir}" type="dir" />
            <!--<then></then>-->
            <else>
                <composer composer="${basedir}/composer.phar" command="update"></composer>
            </else>
        </if>
    </target>

    <!-- レポートなどを格納するフォルダ作成する処理-->
    <target name="prepare" description="prepare">
        <phingcall target="clean" />
        <phingcall target="vendor_update" />
        <mkdir dir="${reports.dir}" />
        <mkdir dir="${test.report.dir}" />
        <mkdir dir="${doc.dir}" />
    </target>

    <!-- 既存フォルダ削除する処理　-->
    <target name="clean" description="clean">
        <delete dir="${reports.dir}" />
        <delete dir="${test.report.dir}" />
        <delete dir="${doc.dir}" />
    </target>

    <!-- PHPCS -->
    <target name="phpcs" description= "Generate phpcs.xml using PHP_CodeSniffer" >
        <exec executable= "phpcs" output= "${reports.dir}/phpcs.xml" >
            <arg line= "
            --report=checkstyle
            --standard=PSR2
            --extensions=php
            ${app.dir}" />
        </exec>
    </target>

    <!-- PHPMD -->
    <target name="phpmd" >
        <phpmd rulesets= "codesize,unusedcode,design,naming" >
            <fileset dir= "${app.dir}" >
                <include name= "**/*.php" />
                <exclude name= "**/*Test.php" />
            </fileset>
            <formatter type= "xml" outfile= "${reports.dir}/pmd.xml" />
        </phpmd>
    </target>

    <!-- PHPCPD -->
    <target name="phpcpd" >
        <phpcpd>
            <fileset dir= "${app.dir}" >
                <include name= "**/*.php" />
                <exclude name= "**/*Test.php" />
            </fileset>
            <formatter type= "pmd" outfile= "${reports.dir}/cpd.xml" />
        </phpcpd>
    </target>

    <target name="phpunit" description="run UnitTest with code coverage">
        <coverage-setup database="${test.report.dir}/coverage.db">
            <fileset dir="${test.dir}">
                <include name="**/*Test.php" />
                <exclude name="**/*TestWithDB.php"/>
            </fileset>
        </coverage-setup>
        <phpunit codecoverage="true">
            <batchtest>
                <fileset dir="${test.dir}">
                    <include name="**/*Test.php" />
                </fileset>
            </batchtest>
        </phpunit>
        <coverage-report outfile="${test.report.dir}/coverage.xml">
            <report todir="${test.report.dir}" />
        </coverage-report>
    </target>

    <target name="phpdoc">
        <delete dir="phpdoc" includeemptydirs="true" />
        <mkdir dir="phpdoc" />
        <exec dir="." command="
            ${vendor.dir}/bin/phpdoc
            -d ${app.dir}
            -t ${doc.dir}
        "/>
    </target>
</project>
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
    }

    public function bootstrap()
    {
        $path = $this->appRoot . '/bootstrap.php';
        ob_start();
echo "<?php" ?>

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new \Exception("STRICT: $errno $errstr $errfile $errline");
});
require realpath(__DIR__ . '/../vendor/autoload.php');
return \Hoimi\Router::getInstance()->setRoutes(array(
    '/batch_request' => 'Hoimi\BatchRequest',
<?php foreach ($this->schemes as $scheme):?>
    '/<?php echo $scheme->getName() ?>' => '<?php echo $this->appName ?>\actions\<?php echo StringUtil::camelize($scheme->getName())?>',
<?php endforeach;?>
));
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
    }

    public function config ()
    {
        $path = $this->appRoot . '/resources/config.php';
        ob_start();
        echo "<?php" ?>
return new \Hoimi\Config(__FILE__);
<?php
        $this->gracefulSave($path, ob_get_clean(), $this->force);
        $path = $this->appRoot . '/resources/database.php';
        ob_start();
        echo "<?php"?>
return array(
    'host' => '<?php echo $this->config['host']?>',
    'user' => '<?php echo $this->config['user']?>',
    'pass' => '<?php echo $this->config['pass']?>',
    'database' => '<?php echo $this->config['db']?>',
    // if you use a scruit migration function, create empty database and set it to this variable.
    'workScheme' => null,
);
<?php
        $this->gracefulSave($path, ob_get_clean(), $this->force);
        $path = $this->appRoot . '/resources/session.php';
        ob_start();?>
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
        $this->gracefulSave($path, ob_get_clean(), $this->force);
        $path = $this->appRoot . '/resources/log.php';
        ob_start();?>
return array (
    'directory' => '/tmp',
    'level' => \Monolog::Logger::INFO,
);
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
        system("mysqldump -u " . $this->config['user'] . "-p". $this->config['pass'] . "-h " . $this->config['host'] . "--no-data > $this->appRoot/resources/create_table.sql");
    }
    public function actions()
    {

        foreach ($this->schemes as $scheme) {
            $path = $this->appRoot . '/actions/' . StringUtil::camelize($scheme->getName()) . '.php';
            ob_start();
echo "<?php" ?>

namespace <?php echo $this->appName ?>\actions;

use \Hoimi\Response\Json;
use \Mahotora\DatabaseSessionImpl;
class <?php echo StringUtil::camelize($scheme->getName()) ?> extends \Hoimi\BaseAction
{
    private $dao = null;
    public function get()
    {
        $id = $this->getRequest()->get('id');
        $response = null;
        if ($id) {
            $data = $this->getDao()->find($id);
            $response = new \Hoimi\Response\Json($data);
        } else {
            $response = new \Hoimi\Response\NotFound();
        }
        return $response;
    }

    public function post()
    {
        $request = $this->getRequest();
        $validationResult = \Hoimi\Validator::validate($request, array(
<?php foreach ($scheme->getColumns() as $column):?>
            '<?php echo $column->getName()?>' => <?php echo preg_replace('#\s*,\s*\)#', ')', preg_replace('#\(\s*#', '(', str_replace("\n", "", var_export($column->validatorDefinition(), true))))?>,
<?php endforeach;?>
        ));
        if ($validationResult) {
            throw new \Hoimi\Exception\ValidationException($validationResult);
        }

        $id = $request->get('id');
        $response = null;
        if ($id) {
            $data = $this->getDao()->find($id);
        }
        if (!$data) {
            $data = new \stdClass();
        }
<?php foreach ($scheme->getColumns() as $column):?>
        $data-><?php echo $column->getName()?> = $request->get('<?php echo $column->getName()?>');
<?php endforeach;?>
        $this->getDao()->save($data);
        $response = new \Hoimi\Response\Json($data);
        return $response;
    }

    public function setDao($dao)
    {
        $this->dao = $dao;
    }

    public function getDao()
    {
        if ($this->dao === null) {
            $this->dao = new \<?php echo StringUtil::camelize($this->appName)?>\classes\dao\<?php echo StringUtil::camelize($scheme->getName())?>Dao(
                DatabaseSessionFactory::build($this->getConfig()->get('database'))
            );
        }
        return $this->dao;
    }
}
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
        }
    }

    public function dao()
    {
        foreach ($this->schemes as $scheme) {
            $path = $this->appRoot . '/classes/Dao/' . StringUtil::camelize($scheme->getName()) . 'Dao.php';
            $selectOne = $scheme->createSelectOne();
            $deleteOne = $scheme->createDeleteOne();
            ob_start();
echo "<?php" ?>

namespace <?php echo $this->appName ?>\classes\dao;

class <?php echo StringUtil::camelize($scheme->getName()) ?>Dao extends \Mahotora\BaseDao
{
    public function getTableName()
    {
            return '<?php echo $scheme->getName() ?>';
    }
<?php if ($scheme->isAutoIncrement()):?>

    public function save($entity)
    {
        parent::save($entity);
        if (<?php echo implode(' && ', array_map(function($column) {return '!isset($entity->' . $column->getName() . ')';}, $scheme->getPrimaryKeys()))?>) {
            $id = $this->getDatabaseSession()->lastInsertId();
<?php foreach ($scheme->getPrimaryKeys() as $column):?>
            $entity-><?php echo $column->getName()?> = $id;
<?php endforeach;?>
        }
    }
<?php endif;?>

    public function delete($id)
    {
        $this->getDatabaseSession()->executeNoResult(
            "<?php echo $deleteOne['SQL']?>",
            "<?php echo $deleteOne['marker'] ?>",
            is_array($id) ? $id : array ($id)
        );
    }

    public function find($id)
    {
        $result = $this->getDatabaseSession()->find(
            "<?php echo $selectOne['SQL'] ?>",
            "<?php echo $selectOne['marker'] ?>",
            is_array($id) ? $id : array ($id)
        );
        return $result ? $result[0] : null;
    }
}
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
        }
    }

    public function test ()
    {
ob_start(); echo "<?php"?>

require_once __DIR__ . '/../vendor/autoload.php';

<?php $this->gracefulSave($this->testRoot . '/bootstrap.php', ob_get_clean(), $this->force); ob_start(); echo '<?xml version="1.0"?>'?>

<phpunit
    bootstrap="./tests/bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    verbose="true"
    stopOnFailure="true"
    processIsolation="false"
    backupGlobals="false"
    syntaxCheck="true"
    >
    <testsuite name="<?php echo $this->appName?>">
        <directory>./tests</directory>
    </testsuite>
</phpunit>
<?php
        $this->gracefulSave($this->root . '/src/phpunit.xml', ob_get_clean());
        foreach ($this->schemes as $scheme) {
            $path = $this->testRoot . '/classes/Dao/' . StringUtil::camelize($scheme->getName()) . 'DaoTest.php';
            $save = $scheme->createSave();
            $selectOne = $scheme->createSelectOne();
ob_start(); echo "<?php" ?>

namespace <?php echo $this->appName ?>\classes\dao;

class <?php echo StringUtil::camelize($scheme->getName()) ?>DaoTest extends \PHPUnit_Framework_TestCase
{
    private $target = null;
    private $databaseSession = null;

    public function setUp()
    {
        $this->databaseSession = \Phake::mock('Mahotora\DatabaseSessionImpl');
        $this->target = new \<?php echo $this->appName ?>\classes\dao\<?php echo StringUtil::camelize($scheme->getName()) ?>Dao($this->databaseSession);
    }

    public function testFind()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        \Phake::when($this->databaseSession)->find(
            "<?php echo $selectOne['SQL']?>",
            "<?php echo $selectOne['marker']?>",
            <?php echo $this->dumpArray($selectOne['dummyKeys'], 12)?>
        )->thenReturn(array($entity));
        $result = $this->target->find(<?php echo $this->dumpArray($selectOne['dummyKeys'], 8)?>);
        \Phake::verify($this->databaseSession)->find(
            "<?php echo $selectOne['SQL']?>",
            "<?php echo $selectOne['marker']?>",
            <?php echo $this->dumpArray($selectOne['dummyKeys'], 12)?>

        );
        $this->assertSame($entity, $result);
    }

    public function testFindNoResult()
    {
        $entity = (object)<?php echo $this->dumpHash($selectOne['dummyHash'], 8)?>;
        \Phake::when($this->databaseSession)->find(
            "<?php echo $selectOne['SQL']?>",
            "<?php echo $selectOne['marker']?>",
            <?php echo $this->dumpArray($selectOne['dummyKeys'], 12)?>
        )->thenReturn(array());
        $result = $this->target->find(<?php echo $this->dumpArray($selectOne['dummyKeys'], 8)?>);
        \Phake::verify($this->databaseSession)->find(
            "<?php echo $selectOne['SQL']?>",
            "<?php echo $selectOne['marker']?>",
            <?php echo $this->dumpArray($selectOne['dummyKeys'], 12)?>

        );
        $this->assertSame(null, $result);
    }
}
<?php $this->gracefulSave($path, ob_get_clean(), $this->force);
        }
    }

    public function gracefulSave ($path, $data, $force = false)
    {
       if ($force || !is_file($path)){
           file_put_contents($path, $data);
           print  "$path is saved\n";
       } else {
           print  "$path is skip\n";
       }
    }

    public function dumpHash($arr, $level, $trim = true)
    {ob_start();?>
<?php echo str_repeat(' ', $level)?>array(
<?php foreach($arr as $key => $value):?>
<?php if (gettype($value) === 'string'):?>
<?php echo str_repeat(' ', $level + 4)?>"<?php echo str_replace('"', '\\"', $key)?>" => "<?php echo str_replace('"', '\\"', $key)?>",
<?php else:?>
<?php echo str_repeat(' ', $level + 4)?>"<?php echo str_replace('"', '\\"', $key)?>" => <?php echo $value?>,
<?php endif;?>
<?php endforeach;?>
<?php echo str_repeat(' ', $level)?>)
    <?php return $trim ? trim(ob_get_clean()) : ob_get_clean();
    }

    public function dumpArray($arr, $level, $trim = true)
    {ob_start();?>
<?php echo str_repeat(' ', $level)?>array(
<?php foreach($arr as $value):?>
<?php if (gettype($value) === 'string'):?>
<?php echo str_repeat(' ', $level + 4)?>"<?php echo str_replace('"', '\\"', $value)?>",
<?php else:?>
<?php echo str_repeat(' ', $level + 4)?><?php echo $value?>,
<?php endif;?>
<?php endforeach;?>
<?php echo str_repeat(' ', $level)?>)
    <?php return $trim ? trim(ob_get_clean()) : ob_get_clean();
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
<?php    }
}
