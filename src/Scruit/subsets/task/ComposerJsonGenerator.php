<?php
/**
 * Date: 15/11/09
 * Time: 21:28.
 */

namespace Scruit\subsets\task;


class ComposerJsonGenerator implements Generatable
{
    private $appName = null;
    public function __construct($appName)
    {
        $this->appName = $appName;
    }

    /**
     * @param array $schemes
     * @return TaskResult[]
     */
    public function getContents(array $schemes)
    {
        ob_start();
?>
{
    "name": <?php echo json_encode($this->appName)?>,
    "autoload" : {
        "psr-4" : {
            "<?php echo $this->appName?>\\" : "app"
        }
    },
    "repositories": [
        {
            "url": "https://github.com/aainc/Hoimi.git",
            "type": "git"
        },
        {
            "url": "https://github.com/aainc/Scruit.git",
            "type": "git"
        },
        {
            "url": "https://github.com/aainc/Mahotora.git",
            "type": "git"
        }
    ],
    "require-dev": {
        "phing/phing": "2.*",
        "phake/phake": "2.*",
        "PHPUnit/phpunit": "3.7.*",
        "phpdocumentor/phpdocumentor" : "*",
        "sebastian/phpcpd" : "2.x",
        "phpmd/phpmd" : "~2.2",
        "fabpot/php-cs-fixer": "1.10.2"
    },
    "require": {
        "aainc/hoimi": "dev-master",
        "aainc/mahotora": "dev-master",
        "aainc/scruit": "dev-master",
        "monolog/monolog": "@stable"
    }
}
<?php
        return array(new TaskResult('src/composer.json', ob_get_clean()));
    }

    public function getTaskName()
    {
        return 'composer';
    }
}