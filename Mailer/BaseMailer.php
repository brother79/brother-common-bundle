<?php

namespace Brother\CommonBundle\Mailer;
use Brother\CommonBundle\Event\Events;
use Brother\CommonBundle\Event\MailEvent;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for the Mailer.
 */
abstract class BaseMailer implements MailerInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    protected $mailer;

    /**
     * Constructor
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     * @param \Symfony\Component\Templating\EngineInterface $templating
     * @param array $config
     */
    public function __construct(EventDispatcherInterface $dispatcher, \Swift_Mailer $mailer, EngineInterface $templating, $config)
    {
        $this->config = $config;
        $this->dispatcher = $dispatcher;
        $this->templating = $templating;
        $this->mailer = $mailer;
    }

    /**
     * @param MailerEntryInterface $entry
     *
     * @return mixed
     */
    public function sendAdminNotification(MailerEntryInterface $entry)
    {
        $emailTitle = str_replace(
            ['{name}', '{email}'],
            array($entry->getName(), $entry->getEmail()),
            $this->config['notification']['title']
        );

        $mailOptions = [];
        $mailOptions['subject'] = $emailTitle;
        $mailOptions['from'] = $this->config['notification']['from'];
        $mailOptions['to'] = $this->config['notification']['to'];
        $mailOptions['body'] = $this->templating->render(
            $this->config['notification']['view'],
            ['entry' => $entry]
        );

        $event = new MailEvent($entry, $mailOptions);
        $this->dispatcher->dispatch(Events::ENTRY_PRE_NOTIFY, $event);

        if ($event->isPropagationStopped()) {
            return false;
        }

        $this->sendEmail($mailOptions);
        $this->dispatcher->dispatch(Events::ENTRY_POST_NOTIFY, $event);

        return true;
    }

    /**
     * @param array $options
     */
    public function sendEmail(array $options)
    {
        if (null !== $this->mailer) {
            $message = \Swift_Message::newInstance()
                ->setSubject($options['subject'])
                ->setFrom($options['from'])
                ->setTo($options['to'])
                ->setBody($options['body']);

            if (isset($options['cc'])) {
                $message->setCc($options['body']);
            }

            $this->mailer->send($message);
        }
    }

}
