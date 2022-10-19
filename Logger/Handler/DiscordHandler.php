<?php


namespace Brother\CommonBundle\Logger\Handler;

use Brother\CommonBundle\Logger\Processor\LineFileProcessor;
use DiscordHandler\DiscordHandler as BaseDiscordHandler;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;

/**
 * Форматируем под наш дискорд
 *
 * Class DiscordHandler
 * @package App\logs\Handler
 */
class DiscordHandler extends BaseDiscordHandler {

    /**
     * DiscordHandler constructor.
     *
     * @param        $webHooks
     * @param string $name
     * @param string $subName
     * @param int    $level
     * @param bool   $bubble
     */
    public function __construct($webHooks, $name = '', $subName = '', $level = Logger::DEBUG, $bubble = true) {
        parent::__construct($webHooks, $name, $subName, $level, $bubble);
        $this->pushProcessor(new LineFileProcessor());
        $this->getConfig()
            ->setMultiMsg(true)
            ->setMaxMessageLength(2000) // at least 50 characters
            ->setTemplate("**[{datetime}]** {name}.__{levelName}__: {message} *{extra.line}* [**{extra.domain}**]");
    }

    /**
     * @param array $record
     *
     * @return void
     * @throws GuzzleException
     */
    protected function write(array $record): void {
        if ($this->config->isEmbedMode()) {
            $parts = [[
                'embeds' => [[
                    'title' => $record['level_name'],
                    'description' => $this->splitMessage($record['message'])[0],
                    'timestamp' => $record['datetime']->format($this->config->getDatetimeFormat()),
                    'color' => $this->levelColors[$record['level']],
                ]]
            ]];
        } else {
            $content = strtr($this->config->getTemplate(), [
                '{datetime}' => $record['datetime']->format($this->config->getDatetimeFormat()),
                '{name}' => $this->config->getName(),
                '{subName}' => $this->config->getSubName(),
                '{levelName}' => $record['level_name'],
                '{message}' => $record['message'],
                '{context.collection}' => $record['context']['collection'] ?? '',
                '{extra.domain}' => $record['extra']['domain'] ?? '',
                '{extra.line}' => $record['extra']['line'] ?? ''
            ]);
            $parts = array_map(function ($message) {
                return [
                    'content' => $message
                ];
            }, $this->splitMessage($content));
        }

//        $webHooks = !empty($record['context'][Logs::LOG_TAG_DISCORD]) ? [$record['context'][Logs::LOG_TAG_DISCORD]] : $this->config->getWebHooks();
        $webHooks = $this->config->getWebHooks();

        foreach ($webHooks as $webHook) {
            foreach ($parts as $part) {
                $this->send($webHook, $part);
            }
        }
    }

}
