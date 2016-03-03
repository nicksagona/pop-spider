<?php

namespace PopSpider;

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
