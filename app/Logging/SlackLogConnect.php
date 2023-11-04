<?php

namespace App\Logging;

use App\Logging\SlackHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class SlackLogConnect
{
    /**
     * @param array $config
     *
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $handler = new SlackHandler(
            $config['token'],
            $config['channel'],
            $config['level']
        );
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);

        return new Logger('slack', [$handler]);
    }
}
