<?php
/**
 * Pop Spider Web SEO Tool
 *
 * @link       https://github.com/nicksagona/pop-spider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 */

/**
 * @namespace
 */
namespace PopSpider\Controller;

use Pop\Console\Console;
use Pop\Controller\AbstractController;
use Pop\View\View;
use PopSpider\Model\Crawler;
use PopSpider\Model\UrlQueue;

/**
 * Console controller class
 *
 * @category   PopSpider
 * @package    PopSpider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 * @version    4.0.0
 */
class ConsoleController extends AbstractController
{

    /**
     * @var Console
     */
    protected $console;

    /**
     * @var Crawler
     */
    protected $crawler;

    public function __construct()
    {
        $this->console = new Console(160, '    ');
    }

    public function help()
    {
        $helpMessage  = './spider ' . $this->console->colorize('help', Console::BOLD_YELLOW) . "\t\t\t\t\t\tDisplay this help screen." . PHP_EOL;
        $helpMessage .= './spider ' . $this->console->colorize('crawl', Console::BOLD_YELLOW) . " " . $this->console->colorize("[--dir=] [--tags=] [--speed=] [--save]", Console::BOLD_CYAN) . " " . $this->console->colorize("<url>", Console::BOLD_GREEN) . "\tCrawl the URL." . PHP_EOL . PHP_EOL;
        $helpMessage .= 'The optional ' . $this->console->colorize("[--dir=]", Console::BOLD_CYAN) . ' parameter allows you to set the output directory for the results report.' . PHP_EOL;
        $helpMessage .= 'The optional ' . $this->console->colorize("[--tags=]", Console::BOLD_CYAN) . ' parameter allows you to set additional tags to scan for in a comma-separated list.' . PHP_EOL;
        $helpMessage .= 'The optional ' . $this->console->colorize("[--speed=]", Console::BOLD_CYAN) . ' parameter will throttle the speed between each request in seconds.' . PHP_EOL;
        $helpMessage .= 'The optional ' . $this->console->colorize("[--save]", Console::BOLD_CYAN) . ' parameter will save the site files into a directory.' . PHP_EOL . PHP_EOL;
        $helpMessage .= 'Example:' . PHP_EOL . PHP_EOL;
        $helpMessage .= '$ ./spider crawl --dir=seo-report --tags=b,u --save http://www.mydomain.com/';

        $this->console->write($helpMessage);
        $this->console->send();
    }

    public function crawl($url, $options = [])
    {
        $this->console->write('Crawling: ' . $url);
        $this->console->write();

        $speed   = (!empty($options['speed'])) ? (int)$options['speed'] : 0;
        $dir     = (!empty($options['dir'])) ? $options['dir'] : 'output';
        $saveDir = (!empty($options['save'])) ? $dir . '-site' : null;
        $tags    = (!empty($options['tags'])) ? explode(',', $options['tags']) : [];
        $start   = time();

        if (!empty($saveDir)) {
            if (!file_exists($saveDir)) {
                mkdir($saveDir);
            }
        }

        $urlQueue      = new UrlQueue($url);
        $this->crawler = new Crawler($urlQueue, $tags);

        while ($nextUrl = $urlQueue->next()) {
            $result = $this->crawler->crawl($saveDir);

            if (!empty($result['content-type'])) {
                if (stripos($result['content-type'], 'text/html') !== false) {
                    $this->console->write($nextUrl, false);
                    $this->console->send();
                    if (floor($result['code'] / 100) == 4) {
                        $color = Console::BOLD_RED;
                    } else if (floor($result['code'] / 100) == 3) {
                        $color = Console::BOLD_CYAN;
                    } else {
                        $color = Console::BOLD_GREEN;
                    }
                    $this->console->write($this->console->colorize($result['code'] . ' ' . $result['message'], $color));
                    $this->console->send();
                } else {
                    $this->console->write('[ ' . $result['content-type'] . ' ] ' . urldecode($result['url']));
                    $this->console->send();
                }
            } else {
                $this->console->write('[ No Result ] (' . $this->console->colorize($nextUrl, Console::BOLD_YELLOW) . ')');
                $this->console->send();
            }

            if (!empty($speed)) {
                sleep($speed);
            }
        }

        $this->output($dir);

        $this->console->write();
        $this->console->write($this->crawler->getTotal() . ' Total URLs crawled in ' . (time() - $start) . ' seconds.');
        $this->console->write($this->crawler->getTotalHtml() . ' HTML URLs crawled.');
        $this->console->write();
        $this->console->write($this->console->colorize($this->crawler->getTotalOk() . ' OK', Console::BOLD_GREEN));
        $this->console->write($this->console->colorize($this->crawler->getTotalRedirects() . ' Redirects', Console::BOLD_CYAN));
        $this->console->write($this->console->colorize($this->crawler->getTotalErrors() . ' Errors', Console::BOLD_RED));
        $this->console->write($this->crawler->getTotalImages() . ' Images');
        $this->console->write($this->crawler->getTotalOther() . ' Other');
        $this->console->send();
    }

    public function error()
    {
        $this->console->write($this->console->colorize('Sorry, that command was not valid.', Console::BOLD_RED));
        $this->console->write();
        $this->console->write('./spider help for help');
        $this->console->send();
    }

    protected function output($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        if (!file_exists($dir . '/css')) {
            mkdir($dir . '/css');
        }
        if (!file_exists($dir . '/js')) {
            mkdir($dir . '/js');
        }

        copy(__DIR__ . '/../../data/assets/css/styles.css', $dir . '/css/styles.css');
        copy(__DIR__ . '/../../data/assets/js/scripts.js', $dir . '/js/scripts.js');

        $data = [
            'base'  => $this->crawler->getBaseUrl(),
            'urls'  => $this->crawler->getCrawled()
        ];

        $index   = new View(__DIR__ . '/../../view/index.phtml', $data);
        $sitemap = new View(__DIR__ . '/../../view/sitemap.php', $data);

        file_put_contents($dir . '/index.html', $index->render());
        file_put_contents($dir . '/sitemap.xml', $sitemap->render());
    }

}
