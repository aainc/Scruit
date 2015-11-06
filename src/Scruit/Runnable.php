<?php
namespace Scruit;
interface Runnable
{
    public function getName();
    public function run($args);
    public function doc();
}
