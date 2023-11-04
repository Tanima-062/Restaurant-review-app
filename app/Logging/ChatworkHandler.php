<?php

namespace App\Logging;

use App;
use GuzzleHttp\Client;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class ChatworkHandler extends AbstractProcessingHandler
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $room;

    /**
     * ChatWorkHandler constructor.
     *
     * @param int $level
     */
    public function __construct(string $token, string $room, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->token = $token;
        $this->room = $room;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $client = new Client();

        $url = "https://api.chatwork.com/v2/rooms/{$this->room}/messages";

        $body = sprintf(
            '[info][title]【APIログ】[%s] [%s] %s[/title]%s[/info]',
            $record['channel'],
            $record['level_name'],
            $record['message'],
            $record['context']['body']
        );

        if (App::environment(['develop', 'production'])) {
            $res = $client->post($url, [
                'headers' => ['X-ChatWorkToken' => $this->token],
                'form_params' => ['body' => $body],
            ]);
        }
    }
}
