<?php

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
