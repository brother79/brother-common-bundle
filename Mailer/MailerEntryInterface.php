<?php

namespace Brother\CommonBundle\Mailer;

/**
 * Interface to be implemented by the comment class.
 */
 
interface MailerEntryInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getEmail();


}
