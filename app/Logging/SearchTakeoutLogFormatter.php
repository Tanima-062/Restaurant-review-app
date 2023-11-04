<?php

namespace App\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Session;

class SearchTakeoutLogFormatter extends NormalizerFormatter
{
    /**
     * @var array
     */
    private $keys = [
        'code',
    ];

    public function format($record): string
    {
        $formatted = parent::format($record);
        $segments = [
            'params' => $formatted['message'],
            '16_session' => substr(Session::getId(), 0, 16),
        ];

        foreach ($this->keys as $key) {
            if (isset($formatted['context'][$key])) {
                $segments[$key] = $formatted['context'][$key];
            }
        }

        return json_encode($segments).PHP_EOL;
    }
}
