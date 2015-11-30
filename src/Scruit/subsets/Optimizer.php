<?php
namespace Scruit\subsets;
class Optimizer implements \Scruit\Runnable
{
    protected $libRoot = null;
    protected $appRoot = null;
    public function __construct ()
    {
        $this->libRoot = dirname(dirname(dirname(dirname(__DIR__))));
        $this->appRoot = $_SERVER['PWD'] . '/app';
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
                    $str = `php -w $row`;
                    $str = preg_replace('#^<\?php#', '', $str, 1);
                    file_put_contents($path, trim($str) . "\n", FILE_APPEND);
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
                    $this->libRoot . '/hoimi/src/Hoimi/Response.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Gettable.php',
                    $this->libRoot . '/hoimi/src/Hoimi/ArrayContainer.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Config.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Request.php',
                    $this->libRoot . '/hoimi/src/Hoimi/BaseAction.php',
                    $this->libRoot . '/hoimi/src/Hoimi/BaseException.php',
                    $this->libRoot . '/hoimi/src/Hoimi/BaseRouter.php',
                    $this->libRoot . '/hoimi/src/Hoimi/BatchRequest.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Router.php',
                    $this->libRoot . '/hoimi/src/Hoimi/UploadFile.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Validator.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Exception/ForbiddenException.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Exception/NotFoundException.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Exception/ValidationException.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Response/Json.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Response/Error.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Response/ErrorJson.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Response/Forbidden.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Response/Html.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Response/NotFound.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Session.php',
                    $this->libRoot . '/hoimi/src/Hoimi/Session/DatabaseDriver.php',
                ),
            ),
            'Mahotora' => array(
                'output' => $this->appRoot . '/hoimi-all.php',
                'list' => array(
                    $this->libRoot . '/mahotora/src/Mahotora/BaseDao.php',
                    $this->libRoot . '/mahotora/src/Mahotora/BaseQuery.php',
                    $this->libRoot . '/mahotora/src/Mahotora/DatabaseSession.php',
                    $this->libRoot . '/mahotora/src/Mahotora/DatabaseSessionFactory.php',
                    $this->libRoot . '/mahotora/src/Mahotora/DatabaseSessionImpl.php',
                    $this->libRoot . '/mahotora/src/Mahotora/InsertQuery.php',
                    $this->libRoot . '/mahotora/src/Mahotora/MultiDatabaseSession.php',
                    $this->libRoot . '/mahotora/src/Mahotora/SaveQuery.php',
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

