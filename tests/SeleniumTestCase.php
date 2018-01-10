<?php
namespace YourTest;

require_once 'bootstrap.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class SeleniumTestCase
{
    const SELENIUM_DRIVER = 'chrome';
    const SELENIUM_HOST = 'https://support.bittrex.com/hc/en-us/categories/200236600-News-and-Announcements';
    /** @var \Facebook\WebDriver\Remote\RemoteWebDriver */
    protected $browser;
    public function __construct()
    {
        // selenium
        $host = 'http://localhost:4444/wd/hub';
        switch (static::SELENIUM_DRIVER) {
            case 'chrome':
                $capability = DesiredCapabilities::chrome();
                break;
            default:
                // 기본값은 firefox
                $capability = DesiredCapabilities::firefox();
        }
        $capability->setCapability('acceptSslCerts', false);
        $this->browser = RemoteWebDriver::create($host, $capability);
    }
    public function tearDown()
    {
        if (isset($this->browser)) {
            $this->browser->close();
        }
    }

    public function onPage($path = '/')
    {
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        $this->browser->get(static::SELENIUM_HOST . $path);

    }
    /**
     * @param string $selector
     * @return \Facebook\WebDriver\Remote\RemoteWebElement[]
     */
    public function getElements($selector)
    {
        return $this->browser->findElements(WebDriverBy::cssSelector($selector));
    }
    /**
     * @param string $selector
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function getElement($selector)
    {
        return $this->browser->findElement(WebDriverBy::cssSelector($selector));
    }
    /**
     * @param string $selector
     * @param string $text
     */
    public function doFillInput($selector, $text)
    {
        $this->getElement($selector)->sendKeys($text);
    }
    /**
     * @param string $selector
     */
    public function doClick($selector)
    {
        $this->getElement($selector)->click();
    }

    public function getSource()
    {
        return $this->browser->getPageSource();
    }
}

$a = new SeleniumTestCase();
$a->onPage('/');
sleep(5);
var_dump($a->getSource());
