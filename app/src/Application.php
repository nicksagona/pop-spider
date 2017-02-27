<?php
/**
 * Pop Spider Web SEO Tool
 *
 * @link       https://github.com/nicksagona/pop-spider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 */

/**
 * @namespace
 */
namespace PopSpider;

/**
 * Application class
 *
 * @category   PopSpider
 * @package    PopSpider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 * @version    3.0.0
 */
class Application extends \Pop\Application
{

    public function bootstrap($autoloader = null)
    {
        parent::bootstrap($autoloader);

        $this->on('app.route.pre', function(){
            echo PHP_EOL;
            echo '    Pop Spider' . PHP_EOL;
            echo '    ----------' . PHP_EOL . PHP_EOL;
        });

        $this->on('app.dispatch.post', function(){
            echo PHP_EOL . PHP_EOL;
        });
    }

}
