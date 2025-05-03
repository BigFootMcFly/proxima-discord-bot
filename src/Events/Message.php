<?php

namespace Events;

use Core\Events\MessageCreate;
use Discord\Parts\Channel\Message as DiscordMessage;
use Discord\Discord;

/**
 * Event handler for the "Message" event
 */
class Message implements MessageCreate
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Handles the event
     *
     * @param DiscordMessage $message
     * @param Discord $discord
     *
     * @return void
     *
     */
    public function handle(DiscordMessage $message, Discord $discord): void
    {
        //
    }
}
