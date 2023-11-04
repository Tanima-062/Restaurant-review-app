<?php

namespace App\Logging;

use App;
use GuzzleHttp\Client;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class SlackHandler extends AbstractProcessingHandler
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $channel;

    /**
     * ChatWorkHandler constructor.
     *
     * @param int $level
     */
    public function __construct(string $token, string $channel, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->token = $token;
        $this->channel = $channel;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $client = new Client();

        $url = "https://slack.com/api/chat.postMessage";

        $title = sprintf(
            '【APIログ】[%s] [%s] %s',
            $record['channel'],
            $record['level_name'],
            $record['message'],
        );

        $message = $record['context']['body'];

        $params = [
            'token' => $this->token,
            'channel' => $this->channel,
            'text' => sprintf("*%s*", $title),
            'icon_emoji' => ':patramp:',
            'username' => '自動投稿システム',
            'attachments' => json_encode([
                [
                    "color" => "good",
                    "text" => $message,
                ],                          // 注意：このカンマなくなったら送れなくなる
            ]),
        ];

        if (App::environment(['develop', 'production'])) {
            $res = $client->post($url, [
                'headers' => [],
                'form_params' => $params,
            ]);
        }
    }
}
