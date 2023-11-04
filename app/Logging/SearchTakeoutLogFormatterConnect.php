<?php

namespace App\Logging;

/**
 * LineFormaterを継承してフォーマットを変更したLineExFormatterをハンドラにツッコミます。
 */
class SearchTakeoutLogFormatterConnect
{
    public function __invoke($logging)
    {
        $exFormatter = new SearchTakeoutLogFormatter();

        foreach ($logging->getHandlers() as $handler) {
            $handler->setFormatter($exFormatter);
        }
    }
}
