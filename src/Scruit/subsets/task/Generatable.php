<?php
/**
 * Date: 15/11/09
 * Time: 18:11.
 */

namespace Scruit\subsets\task;


interface Generatable
{
    /**
     * @param array $schemes
     * @return TaskResult[]
     */
    public function getContents (array $schemes);
    public function getTaskName();
}