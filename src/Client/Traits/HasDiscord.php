<?php

namespace Client\Traits;

use Discord\Discord;

use function Core\env;

/**
 * Function to access the global Discord singleton instance.
 *
 */
trait HasDiscord
{
    protected Discord|null $discord = null;

    // ------------------------------------------------------------------------------------------------------------
    /**
     * Returns the global Discord instance.
     *
     * @return Discord
     *
     */
    protected function getDiscord(): Discord
    {
        if (null === $this->discord) {
            $this->discord = env()->discord;
        }

        return $this->discord;
    }
}
