<?php

namespace PopSpider\Test;

use PopSpider\Model;
use Pop\Application;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $app = new \PopSpider\Application(
            include __DIR__ . '/../app/config/application.php'
        );

        $crawler = new Model\Crawler(new Model\UrlQueue('http://www.popphp.org/'), ['b', 'u']);

        $this->assertInstanceOf('PopSpider\Application', $app);
        $this->assertInstanceOf('PopSpider\Model\Crawler', $crawler);
        $this->assertInstanceOf('PopSpider\Model\UrlQueue', $crawler->getUrlQueue());
        $this->assertEquals('http://www.popphp.org', $crawler->getBaseUrl());
        $this->assertEquals(9, count($crawler->getTags()));
        $this->assertEquals(5, count($crawler->getCrawled()));
        $this->assertEquals(0, count($crawler->getCrawled()['200']));
        $this->assertEquals(0, count($crawler->getCrawled()['30*']));
        $this->assertEquals(0, count($crawler->getCrawled()['40*']));
        $this->assertEquals(0, count($crawler->getCrawled()['images']));
        $this->assertEquals(0, count($crawler->getCrawled()['other']));
        $this->assertEquals(0, $crawler->getTotal());
        $this->assertEquals(0, $crawler->getTotalHtml());
        $this->assertEquals(0, $crawler->getTotalOk());
        $this->assertEquals(0, $crawler->getTotalRedirects());
        $this->assertEquals(0, $crawler->getTotalErrors());
        $this->assertEquals(0, $crawler->getTotalImages());
        $this->assertEquals(0, $crawler->getTotalOther());
    }

    public function testCrawl()
    {
        $app = new \PopSpider\Application(
            include __DIR__ . '/../app/config/application.php'
        );

        $urlQueue = new Model\UrlQueue('http://www.popphp.org/');
        $crawler  = new Model\Crawler($urlQueue);

        while ($nextUrl = $urlQueue->next()) {
            $result = $crawler->crawl();
        }

        $this->assertEquals(200, $result['code']);
        $this->assertGreaterThanOrEqual(1, $crawler->getTotal());
    }

}