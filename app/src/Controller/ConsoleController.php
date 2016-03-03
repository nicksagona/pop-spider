<?php

namespace PopSpider\Controller;

use Pop\Console\Console;
use Pop\Console\Input\Command;
use PopSpider\Model\Crawler;

class ConsoleController extends \Pop\Controller\AbstractController
{

    protected $console;

    public function __construct()
    {
        $this->console = new Console(80, '    ');

        $helpMessage  = './spider ' . $this->console->colorize('help', Console::BOLD_YELLOW) . "\t\t\t\tDisplay this help screen." . PHP_EOL;
        $helpMessage .= './spider ' . $this->console->colorize('crawl', Console::BOLD_YELLOW) . " <url> [--dir=] [--tags=]\tCrawl the URL." . PHP_EOL . PHP_EOL;
        $helpMessage .= 'The optional [--dir=] parameter allows you to set the output directory for the results report. ';
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

        $dir     = (null !== $dir) ? $dir : 'output';
        $tags    = (null !== $tags) ? explode(',', $tags) : [];
        $crawler = new Crawler($url, $dir, $tags);
        $crawler->run();

        $this->console->send();
    }

    public function error()
    {
        $this->console->write($this->console->colorize('Sorry, that command was not valid.', Console::BOLD_RED));
        $this->console->write();
        $this->console->write('./spider help for help');
        $this->console->send();
    }

}
