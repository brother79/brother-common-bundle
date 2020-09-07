<?php

namespace Brother\CommonBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for an event that occurs related to performing 
 * a batch operation on a list of guestbook entries.
 */
class EntriesEvent extends Event
{
    private $ids;

    /**
     * Constructs an event.
     *
     * @param array $ids
     */
    public function __construct($ids)
    {
        $this->ids = $ids;
    }
	
    /**
     * Returns the entry ids for this event.
     *
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
