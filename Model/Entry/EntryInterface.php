<?php

/*
 * This file is part of the BrotherPageBundle package.
 *
 * (c) Yos Okusanya <yos.okusanya@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Brother\CommonBundle\Model\Entry;

/**
 * Interface to be implemented by the comment class.
 */
 
interface EntryInterface
{
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId();

    /**
     * Set id
     *
     * @param string $id
     */
    public function setId($id);

}
