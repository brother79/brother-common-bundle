<?php

namespace Brother\CommonBundle\Mailer;

use Symfony\Component\Form\FormInterface;


/**
 * Mailer Interface
 */
interface MailerInterface
{
    /**
     * @param MailerEntryInterface $comment
     */
    public function sendAdminNotification(MailerEntryInterface $comment);

    /**
     * @param array $options
     */
    public function sendEmail(array $options);

}
