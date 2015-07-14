<?php

namespace Brother\CommonBundle\Event;
use Brother\CommonBundle\Mailer\MailerEntryInterface;

/**
 * An event that occurs related to sending an email.
 */
class MailEvent extends EntryEvent
{
    private $options;


    /**
     * Constructor
     *
     * @param MailerEntryInterface $entry
     * @param array $options
     */
    public function __construct(MailerEntryInterface $entry, array $options)
    {
        parent::__construct($entry);
		
        $this->options = $options;
    }

    /**
     * Returns the mail options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
