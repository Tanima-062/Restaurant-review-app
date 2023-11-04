<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

/**
 * 行番号とかファイル名をとってくれる IntrospectionProcessorをハンドラに突っ込みます。
 */
class CustomProcessorConnect
{
    public function __invoke($logging)
    {
        // 4つくらい上にframeを遡るとちょうど良いやつが取れた。
        $introspectionProcessor = new IntrospectionProcessor(
            Logger::DEBUG,
            [],
            4);

        foreach ($logging->getHandlers() as $handler) {
            $handler->pushProcessor($introspectionProcessor);
        }
    }
}
