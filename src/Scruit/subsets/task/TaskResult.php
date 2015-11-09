<?php
/**
 * Date: 15/11/09
 * Time: 16:55.
 */

namespace Scruit\subsets\task;


class TaskResult
{
    private $fileName = null;
    private $content  = null;

    function __construct($fileName, $content)
    {
        $this->content = $content;
        $this->fileName = $fileName;
    }

    /**
     * @return null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return null
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}