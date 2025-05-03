<?php

namespace Client\Traits;

use Bot\DiscordBot;
use Carbon\Carbon;
use Client\ClientMessages;
use Client\Models\DiscordUser;
use Client\Models\Remainder;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Option as RequestOption;

use function Core\optionChoise;

/**
 * Common functions used in EditRemainder and RemoveRemainder.
 */
trait RemainderListCommand
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Autocompletes the interaction with an error message
     *
     * Send an error for the invalid remainder alias to the client as an autocomplete result
     *
     * @param Interaction $interaction
     *
     * @return false
     *
     */
    protected function invalidRemainderAlias(Interaction $interaction): false
    {
        $result = [];

        $remainderAlias = $interaction->data->options->get('name', 'remainder')->value;

        $result[] = optionChoise(sprintf('Error: The remainder "%s" is not a valid remainder!', $remainderAlias));
        $result[] = optionChoise('Please chose one from the selection list!');
        $interaction->autoCompleteResult($result);
        return false;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Gets the actual remainder from the dispaly list (remainder alias list) or false if not found.
     *
     * @param Interaction $interaction
     * @param array $remainders
     *
     * @return Remainder|false
     *
     */
    protected function getActualRemainder(Interaction $interaction, array $remainders): Remainder|false
    {
        $remainderAlias = $interaction->data->options->get('name', 'remainder')->value;
        // extract the index from the alias
        $result = preg_match('/\(#(\d*)\)/', $remainderAlias, $matches);
        if (1 !== $result) {
            return false;
        }

        $remainderIndex = $matches[1];
        // select the remainder
        $remainder = $remainders[$remainderIndex];

        return $remainder;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Generates autocomplete list for the remainder parameter.
     *
     * @param Interaction $interaction
     * @param RequestOption $option
     *
     * @return array
     *
     */
    protected function autoCompleteRemainder(Interaction $interaction, RequestOption $option, DiscordUser $discordUser): void
    {
        $searchString = $option->value;

        $result = [];

        $index = 0;
        foreach ($discordUser->remainders as $remainder) {
            $message = sprintf(
                "(#%d): %s   ->   \"%s\"",
                $index,
                Carbon::parse($remainder->due_at)
                    ->setTimezone($discordUser->timezone)
                    ->format(DiscordBot::getDateTimeFormat()),
                $remainder->message
            );

            //NOTE: max 100 chars....
            $message = mb_strimwidth($message, 0, 80, '...');

            if ($searchString === '' || false !== stripos($message, $searchString)) {
                $result[] = optionChoise($message);
            }

            $index++;
        }

        $interaction->autoCompleteResult($result);

    }

    /**
     * Send an error for the invalid remainder alias to the client as a response message.
     *
     * @param Interaction $interaction
     * @param string $remainderAlias
     *
     * @return void
     *
     */
    protected function failInvalidRemainderAlias(Interaction $interaction, string $remainderAlias): void
    {
        DiscordBot::respondToInteraction(
            interaction: $interaction,
            template: ClientMessages::errorInvalidRemainderAlias,
            variables: [
                'remainder' => $remainderAlias,
            ]
        );
    }

}
