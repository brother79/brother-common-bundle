<?php

namespace Brother\CommonBundle\Event;

use Brother\CommonBundle\Model\Entry\EntryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for a guestbook entry related event.
 */
class EntryEvent extends Event
{
    private $entry;

    /**
     * Constructor.
     *
     * @param EntryInterface $entry
     */
    public function __construct(EntryInterface $entry)
    {
        $this->entry = $entry;
    }

    /**
     * Returns the guestbook entry for this event.
     *
     * @return EntryInterface
     */
    public function getEntry()
    {
        return $this->entry;
    }
}
