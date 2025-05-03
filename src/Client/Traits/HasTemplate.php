<?php

namespace Client\Traits;

use Client\Template;

/**
 * Function to access the global Template singleton instance.
 *
 */
trait HasTemplate
{
    // ------------------------------------------------------------------------------------------------------------
    /**
     * Returns the global Template instance.
     *
     * @return Template
     *
     */
    protected function getTemplate(): Template
    {
        return Template::getInstance();
    }
}
