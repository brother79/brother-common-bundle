<?php

/*
 * This file is part of the BrotherGuestbookBundle package.
 *
 * (c) Yos Okusanya <yos.okusanya@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Brother\CommonBundle\Event;

use Brother\CommonBundle\Model\Entry\EntryInterface;
use Symfony\Component\EventDispatcher\Event;

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
