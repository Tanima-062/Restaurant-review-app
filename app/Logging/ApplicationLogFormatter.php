<?php

namespace App\Logging;

use Illuminate\Support\Facades\Request;
use Monolog\Formatter\NormalizerFormatter;
use Session;

class ApplicationLogFormatter extends NormalizerFormatter
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
            'datetime' => date('Y-m-d H:i:s', strtotime($formatted['datetime'])),
            'level_name' => $formatted['level_name'],
            'app' => config('app.name'),
            'uri' => Request::path(),
            'method' => Request::method(),
            // 'ip' => Request::getClientIps()[0],
            'ip' => $_SERVER["HTTP_X_FORWARDED_FOR"] ?? Request::getClientIps()[0],
            'api_token' => Request::bearerToken(),
            '16_session' => substr(Session::getId(), 0, 16),
            'message' => $formatted['message'],
            //'extra' => $formatted['extra'],
        ];

        foreach ($this->keys as $key) {
            if (isset($formatted['context'][$key])) {
                $segments[$key] = $formatted['context'][$key];
            }
        }

        return json_encode($segments).PHP_EOL;
    }
}
