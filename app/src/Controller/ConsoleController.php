<?php

namespace PopSpider\Controller;

use Pop\Console\Console;
use Pop\Console\Input\Command;
use Pop\View\View;
use PopSpider\Model\Crawler;

class ConsoleController extends \Pop\Controller\AbstractController
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

        $helpMessage  = './spider ' . $this->console->colorize('help', Console::BOLD_YELLOW) . "\t\t\t\tDisplay this help screen." . PHP_EOL;
        $helpMessage .= './spider ' . $this->console->colorize('crawl', Console::BOLD_YELLOW) . " <url> [--dir=] [--tags=]\tCrawl the URL." . PHP_EOL . PHP_EOL;
        $helpMessage .= 'The optional [--dir=] parameter allows you to set the output directory for the results report.' . PHP_EOL;
        $helpMessage .= 'The optional [--tags=] parameter allows you to set additional tags to scan for in a comma-separated list.' . PHP_EOL . PHP_EOL;
        $helpMessage .= 'Example:' . PHP_EOL . PHP_EOL;
        $helpMessage .= '$ ./spider crawl http://www.mydomain.com/ --dir=seo-report --tags=b,u';

        $help = new Command('help');
        $help->setHelp($helpMessage);
        $this->console->addCommand($help);
    }

    public function help()
    {
        $this->console->write($this->console->getCommand('help')->getHelp());
        $this->console->send();
    }

    public function crawl($url, $dir = null, $tags = null)
    {
        $this->console->write('Crawling: ' . $url);
        $this->console->write();

        $dir  = (null !== $dir) ? $dir : 'output';
        $tags = (null !== $tags) ? explode(',', $tags) : [];

        $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ?
            $_SERVER['HTTP_USER_AGENT'] :
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:16.0) Gecko/20100101 Firefox/16.0';

        $context = [
            'method'     => 'GET',
            'header'     => "Accept-language: en\r\n" . "User-Agent: " . $ua . "\r\n",
            'user_agent' => $ua
        ];

        $start = time();
        $this->crawler = new Crawler($url, $dir, $tags);
        $this->crawler->prepare($this->console);
        $this->crawler->crawl($this->crawler->getBaseUrl(), $context);

        $this->output();

        $crawled = $this->crawler->getCrawled();

        $this->console->write();
        $this->console->write($this->crawler->getTotal() . ' URLs crawled in ' . (time() - $start) . ' seconds.');
        $this->console->write();
        $this->console->write($this->console->colorize(count($crawled['200']) . ' URLs', Console::BOLD_GREEN));
        $this->console->write($this->console->colorize(count($crawled['30*']) . ' Redirects', Console::BOLD_CYAN));
        $this->console->write($this->console->colorize(count($crawled['404']) . ' Errors', Console::BOLD_RED));
        $this->console->send();
    }

    public function error()
    {
        $this->console->write($this->console->colorize('Sorry, that command was not valid.', Console::BOLD_RED));
        $this->console->write();
        $this->console->write('./spider help for help');
        $this->console->send();
    }

    protected function output()
    {
        $dir = $this->crawler->getDir();
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
            'urls'  => $this->crawler->getCrawled(),
            'depth' => $this->crawler->getDepth()
        ];

        $index   = new View(__DIR__ . '/../../view/index.phtml', $data);
        $sitemap = new View(__DIR__ . '/../../view/sitemap.php', $data);

        file_put_contents($dir . '/index.html', $index->render());
        file_put_contents($dir . '/sitemap.xml', $sitemap->render());
    }

}
