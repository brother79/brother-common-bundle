<?php

namespace Brother\CommonBundle\Event;

/**
 * Guestbook Events.
 */
final class Events
{
    /**
     * The CREATE event occurs when the manager creates 
	 * a new guestbook entry instance.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryEvent instance.
     *
     * @var string
     */
    const ENTRY_CREATE = 'brother_guestbook.entry.create';
	
    /**
     * The PRE_PERSIST occurs prior to the manager persisting the entry.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryEvent instance.
	 *
     * Persistence can be aborted by calling $event->isPropagationStopped()
     *
     * @var string
     */
    const ENTRY_PRE_PERSIST = 'brother_guestbook.entry.prePersist';

    /**
     * The POST_PERSIST event occurs after the Guestbook is persisted.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryEvent instance.
     *
     * @var string
     */
    const ENTRY_POST_PERSIST = 'brother_guestbook.entry.postPersist';
	
    /**
     * The PRE_REMOVE event occurs prior to the manager 
	 * removing a guestbook entry.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryEvent instance.
	 *
     * Entry removal can be aborted by calling $event->isPropagationStopped().
     *
     * @var string
     */
    const ENTRY_PRE_REMOVE = 'brother_guestbook.entry.preDelete';

    /**
     * The POST_REMOVE event occurs after removing a guestbook entry.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryEvent instance.
     *
     * @var string
     */
    const ENTRY_POST_REMOVE = 'brother_guestbook.entry.postDelete';

    /**
     * The PRE_DELETE event occurs prior to the manager 
	 * deleting a list of guestbook entries.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryDeleteEvent instance.
	 *
     * Entry deletion can be aborted by calling $event->isPropagationStopped().
     *
     * @var string
     */
	
    const ENTRY_PRE_DELETE = 'brother_guestbook.entry.preDelete';

    /**
     * The POST_DELETE event occurs after deleting a list of guestbook entries.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryDeleteEvent instance.
     *
     * @var string
     */
    const ENTRY_POST_DELETE = 'brother_guestbook.entry.postDelete';

    /**
     * The PRE_UPDATE_STATE event occurs prior to updating 
	 * the state of a list of guestbook entries.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryStateEvent instance.
	 *
     * State update can be aborted by calling $event->isPropagationStopped().
     *
     * @var string
     */
    const ENTRY_PRE_UPDATE_STATE = 'brother_guestbook.entry.preNotify';

    /**
     * The POST_UPDATE_STATE event occurs after updating
	 * the state of a list of guestbook entries.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\EntryStateEvent instance.
     *
     * @var string
     */
    const ENTRY_POST_UPDATE_STATE = 'brother_guestbook.entry.postNotify';

    /**
     * The PRE_REPLY event occurs prior to replying a entry.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\MailEvent instance.
	 *
     * Sending the reply can be aborted by calling $event->isPropagationStopped().
     *
     * @var string
     */	
    const ENTRY_PRE_REPLY = 'brother_guestbook.entry.preReply';

    /**
     * The POST_REPLY event occurs affer sending a reply.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\MailEvent instance.
     *
     * @var string
     */
    const ENTRY_POST_REPLY = 'brother_guestbook.entry.postReply';

    /**
     * The PRE_NOTIFY event occurs prior to sending a 
	 * guestbook entry notification email.
	 *
     * Notification can be aborted by calling $event->isPropagationStopped().
	 *
     * The listener receives a Brother\GuestbookBundle\Event\MailEvent instance.
     *
     * @var string
     */
    const ENTRY_PRE_NOTIFY = 'brother_guestbook.entry.preNotify';

    /**
     * The POST_NOTIFY event occurs after sending a 
	 * guestbook entry notification email.
	 *
     * The listener receives a Brother\GuestbookBundle\Event\MailEvent instance.
     *
     * @var string
     */
    const ENTRY_POST_NOTIFY = 'brother_guestbook.entry.postNotify';
}
