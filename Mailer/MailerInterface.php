<?php

/*
 * This file is part of the BrotherGuestbookBundle package
 *
 * (c) Yos Okus <yos.okusanya@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
