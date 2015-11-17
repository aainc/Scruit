<?php
/**
 * Date: 15/10/20
 * Time: 16:02
 */

namespace Scruit\subsets;


use Scruit\Runnable;

class UnOptimizer implements Runnable
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
        return 'un-optimize';
    }

    public function run($args)
    {
        if (is_file($this->appRoot . '/hoimi-all.php'))            unlink($this->appRoot . '/hoimi-all.php');
        if (is_file($this->appRoot . '/bootstrap.php.org'))        copy($this->appRoot . '/bootstrap.php.org', $this->appRoot . '/bootstrap.php');
        if (is_file($this->appRoot . '/resources/config.php.org')) copy($this->appRoot . '/resources/config.php.org', $this->appRoot . '/resources/config.php');
        if (is_file($this->appRoot . '/bootstrap.php.org'))        unlink($this->appRoot . '/bootstrap.php.org');
        if (is_file($this->appRoot . '/resources/config.php.org')) unlink($this->appRoot . '/resources/config.php.org');
    }

    public function doc()
    {?>
un-optimizer is un-optimize application(for development mode).
unoptimizer delete and rename the file that is created by optimizer.
<?php
    }
}