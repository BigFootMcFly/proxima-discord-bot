<?php

namespace Client\Traits;

use Client\ApiClient;

/**
 * Function to access the global ApiClient singleton instance.
 *
 */
trait HasApiClient
{
    // ------------------------------------------------------------------------------------------------------------
    /**
     * Returns the global ApiClient instance.
     *
     * @return ApiClient
     *
     */
    protected function getApiClient(): ApiClient
    {
        return ApiClient::getInstance();
    }
}
