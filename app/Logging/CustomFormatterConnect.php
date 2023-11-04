<?php

namespace App\Logging;

/**
 * LineFormaterを継承してフォーマットを変更したLineExFormatterをハンドラにツッコミます。
 */
class CustomFormatterConnect
{
    public function __invoke($logging)
    {
        $exFormatter = new ApplicationLogFormatter();

        foreach ($logging->getHandlers() as $handler) {
            $handler->setFormatter($exFormatter);
        }
    }
}
