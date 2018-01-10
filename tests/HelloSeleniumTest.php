<?php

use YourTest\SeleniumTestCase;

require_once 'SeleniumTestCase.php';

class HelloSeleniumTest
{
    private $test;
    public function __construct()
    {
        $this->test = new SeleniumTestCase();
    }

    public function testaaaaa()
    {
        $a = $this->test->onPage('/');
        var_dump($a);
    }

    public function testbbbb()
    {
        $this->onPage('/');
        $this->doClick("a[href='/about']");
        $this->assertSeleniumUrlEquals('/about/');
        $this->assertSeleniumBodyContain('덕질하는 개발개발인간');
    }
}

$test = new HelloSeleniumTest();
//$test->testaaaaa();