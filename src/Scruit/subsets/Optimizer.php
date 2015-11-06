<?php
namespace Scruit\subsets;
class Optimizer implements \Scruit\Runnable
{
    protected $libRoot = null;
    protected $appRoot = null;
    public function __construct ()
    {
        $this->libRoot = dirname(dirname(dirname(dirname(__DIR__))));
        $this->appRoot = $_SERVER['PWD'] . '/src/app';
    }

    public function getName()
    {
        return 'optimize';
    }

    public function run($args)
    {

        foreach ($this->define() as $key => $val) {
            $path = $val['output'];
            if (is_file($path))  unlink($path);
        }
        foreach ($this->define() as $key => $val) {
            $path = $val['output'];
            print "$key is compressing to $path\n";
            if (!is_file($path)) {
                file_put_contents($path, '<?php' . "\n");
            }
            foreach ($val['list'] as $row) {
                if (is_file($row)) {
                    file_put_contents($path, trim(preg_replace('#<\?php#', '', `php -w $row`)) . "\n", FILE_APPEND);
                } else {
                    error_log("$row is not exists.");
                }
            }
        }
        $this->compressConfig();
    }

    public function define()
    {

        return array (
            'Hoimi' => array(
                'output' => $this->appRoot . '/hoimi-all.php',
                'list' => array(
                    $this->libRoot . '/Hoimi/src/Hoimi/Response.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Gettable.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/ArrayContainer.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Config.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Request.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/BaseAction.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/BaseException.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/BaseRouter.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/BatchRequest.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Router.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/UploadFile.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Validator.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Exception/ForbiddenException.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Exception/NotFoundException.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Exception/ValidationException.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Response/Json.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Response/Error.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Response/ErrorJson.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Response/Forbidden.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Response/Html.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Response/NotFound.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Session.php',
                    $this->libRoot . '/Hoimi/src/Hoimi/Session/DatabaseDriver.php',
                ),
            ),
            'Mahotora' => array(
                'output' => $this->appRoot . '/hoimi-all.php',
                'list' => array(
                    $this->libRoot . '/Mahotora/src/Mahotora/BaseDao.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/BaseQuery.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/DatabaseSession.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/DatabaseSessionFactory.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/DatabaseSessionImpl.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/InsertQuery.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/MultiDatabaseSession.php',
                    $this->libRoot . '/Mahotora/src/Mahotora/SaveQuery.php',
                ),
            ),
        );
    }

    public function doc()
    {?>
optimizer speed up a application by cutting back disk accesses.
optimizer compress and concat libraries and config file.
<?php
    }

    public function compressConfig()
    {
        require_once $this->appRoot . '/bootstrap.php';
        $configPath = $this->appRoot . '/resources/config.php';
        $config = require $configPath;
        if (!is_file($this->appRoot . '/resources/config.php.org')) {
            copy($this->appRoot . '/resources/config.php', $this->appRoot . '/resources/config.php.org');
        }
        $string = var_export($config->getConfig(), true);
        ob_start(); echo "<?php\n"  ?>
$config = new \Hoimi\Config();
return $config->setConfig(<?php echo $string ?>);

<?php
        file_put_contents($configPath, ob_get_clean());
        file_put_contents($configPath, `php -w $configPath`);
    }
}

