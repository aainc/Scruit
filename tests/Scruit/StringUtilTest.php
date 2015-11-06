<?php
namespace Scruit;

class StringUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testParseConfig ()
    {
        $result = StringUtil::parseConfig('className="class\"Name"           methodName   =  \'"methodName"\'');
        $this->assertSame('class"Name', $result['className']);
        $this->assertSame('"methodName"', $result['methodName']);
    }

    public function testCamelize ()
    {
        $this->assertSame('HogeFuga', StringUtil::camelize('hoge_fuga'));
    }

    public function testDeCamelize ()
    {
        $this->assertSame('hoge_fuga', StringUtil::decamelize('HogeFuga'));
    }
}

