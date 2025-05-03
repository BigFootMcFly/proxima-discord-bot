<?php

namespace Client\Traits;

use Bot\DiscordBot;
use Client\ClientMessages;
use Client\Models\DiscordUser;
use Discord\Parts\Interactions\Interaction;

use function Core\optionChoise;

/**
 * Functions used to assure, that the discorduser has a valid timezone set.
 *
 * NOTE: discord does not provide a timezone for the user, so to be able to handle/display time correctly,
 *       based on the users own timezone, a timezone must be known/set.
 *
 */
trait AssureTimezoneSet
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fails the interaction if the discordUser has no timezone set.
     *
     * @param Interaction $interaction Interaction object of the discord client
     * @param DiscordUser $discordUser DiscordUser to check for a valid timezone
     *
     * @return bool true if the error was sent to the discord client, false if no action was taken
     *
     */
    public function failIfTimezoneNotSet(Interaction $interaction, DiscordUser $discordUser): bool
    {
        if (!$discordUser->hasTimeZone()) {
            DiscordBot::respondToInteraction(
                interaction: $interaction,
                template: ClientMessages::warningTimezoneNotset
            );
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Sned error optionChoises as the autocomplete list if the discordUser has no timezone set.
     *
     * @param Interaction $interaction Interaction object of the discord client
     * @param DiscordUser $discordUser DiscordUser to check for a valid timezone
     *
     * @return bool true if the error list was sent to the discord client, false if no action was taken
     *
     */
    public function failAutoCompleteIfTimezoneNotSet(Interaction $interaction, DiscordUser $discordUser): bool
    {
        $result = [];
        if (!$discordUser->hasTimeZone()) {
            $result[] = optionChoise("-1");
            $result[] = optionChoise("Warning: you're timezone is not set.");
            $result[] = optionChoise("Please run \"/profile timezone\" to specify your timezone!");
            $interaction->autoCompleteResult($result);
            return true;
        }
        return false;
    }
}
