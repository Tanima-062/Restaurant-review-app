<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class ChatworkLogConnect
{
    /**
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $handler = new ChatworkHandler(
            $config['token'],
            $config['room'],
            $config['level']
        );

        $formatter = new LineFormatter(null, null, true, true);

        $handler->setFormatter($formatter);

        return new Logger('chatwork', [$handler]);
    }
}
