<?php

/*
 * This file is part of the BrotherPageBundle package.
 *
 * (c) Yos Okusanya <yos.okusanya@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
