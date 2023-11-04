<?php

namespace App\Console\Commands;

use Log;

trait BaseCommandTrait
{

    private $className;
    private $timeStart;

    private function getClassName($obj)
    {
        if (!empty($this->className)) {
            return $this->className;
        }

        $this->className = basename(strtr(get_class($obj), '\\', '/'));

        return $this->className;
    }

    private function logPrefix()
    {
        return '['.$this->className.'] ';
    }

    private function start()
    {
        $this->info($this->logPrefix().'##### START #####');
        $this->timeStart = microtime(true);
    }

    private function end()
    {
        $time = microtime(true) - $this->timeStart;
        $this->info($this->logPrefix().'##### END   ##### time: '.$time);
    }
}
