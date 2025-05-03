<?php

namespace Client\Traits;

use Bot\Cache;

/**
 * Function to access the global Cache singleton instance.
 *
 */
trait HasCache
{
    // ------------------------------------------------------------------------------------------------------------
    /**
     * Returns the global Cache instance.
     *
     * @return Cache
     *
     */
    protected function getCache(): Cache
    {
        return Cache::getInstance();
    }
}
