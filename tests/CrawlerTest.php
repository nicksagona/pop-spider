<?php

namespace PopSpider\Test;

use Pop\Application;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $app = new \PopSpider\Application(
            include __DIR__ . '/../app/config/application.php'
        );

        $crawler = new \PopSpider\Model\Crawler('http://www.popphp.org/', 'output2', ['b', 'u']);

        $this->assertInstanceOf('PopSpider\Application', $app);
        $this->assertInstanceOf('PopSpider\Model\Crawler', $crawler);
        $this->assertEquals('http://www.popphp.org', $crawler->getBaseUrl());
        $this->assertEquals('output2', $crawler->getDir());
        $this->assertEquals(0, $crawler->getDepth());
        $this->assertEquals(9, count($crawler->getTags()));
        $this->assertEquals(3, count($crawler->getCrawled()));
        $this->assertEquals(0, count($crawler->getCrawled()['200']));
        $this->assertEquals(0, count($crawler->getCrawled()['30*']));
        $this->assertEquals(0, count($crawler->getCrawled()['404']));
        $this->assertEquals(0, $crawler->getTotal());
    }

}
