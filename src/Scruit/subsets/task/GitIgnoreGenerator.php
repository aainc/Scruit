<?php
/**
 * Date: 15/11/09
 * Time: 18:35.
 */

namespace Scruit\subsets\task;


class GitIgnoreGenerator implements Generatable
{

    /**
     * @param array $schemes
     * @return TaskResult[]
     */
    public function getContents(array $schemes)
    {
        ob_start();?>
VagrantFile
.vagrant
.idea
**/vendor
**/*.bak
**/*.bk
<?php
        return array(new TaskResult('.gitignore', ob_get_clean()));
    }

    public function getTaskName()
    {
        return 'gitignore';
    }
}