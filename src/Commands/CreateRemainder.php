<?php

namespace Commands;

use Bot\DiscordBot;
use Carbon\Carbon;
use Client\ClientMessages;
use Client\Models\DiscordUser;
use Client\Models\Remainder;
use Client\Responses\RemainderResponse;
use Client\Traits\AssureTimezoneSet;
use Client\Traits\HasCache;
use Client\Traits\HasApiClient;
use Client\Traits\HasDiscord;
use Core\Commands\Command;
use Core\Commands\CommandHandler;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use React\Http\Message\Response;
use Discord\Parts\Interactions\Request\Option as RequestOption;

use function Core\isDateTimeValid;
use function Core\optionChoise;

/**
 * The "/rem" command handler.
 *
 * Creates a Remainder for the DiscordUser.
 *
 * @example /rem when <due_at> message <message> [channel] <channel> - Create a remainder.
 *
 */
#[Command]
class CreateRemainder implements CommandHandler
{
    use HasApiClient, HasCache, HasDiscord, AssureTimezoneSet;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Handles the request from the discord client.
     *
     * @param Interaction $interaction
     *
     * @return void
     *
     */
    public function handle(Interaction $interaction): void
    {
        $when = $interaction->data->options->get('name', 'when')->value;
        $message = $interaction->data->options->get('name', 'message')->value;
        $channel = $interaction->data->options->get('name', 'channel')?->value;

        $discordUser = DiscordUser::fromInteraction($interaction);

        $this->getCache()->getDiscordUser($discordUser)->then(
            onFulfilled: function (DiscordUser $discordUser) use ($when, $message, $channel, $interaction) {

                // fail and send error message to the discord client if the discorduser does not have a valid timezone
                if ($this->failIfTimezoneNotSet($interaction, $discordUser)) {
                    return;
                }

                if (!isDateTimeValid($when)) {
                    DiscordBot::respondToInteraction(
                        interaction: $interaction,
                        template: ClientMessages::errorDateTimeNotValid,
                        variables: [
                            'time' => $when,
                        ]
                    );
                    return;
                }

                // get the due_at time based on the discord users timezone
                $due_at = Carbon::parse($when, $discordUser->timezone);

                $newRemainder = new Remainder(
                    id: null,
                    discord_user_id: $discordUser->id,
                    channel_id: $channel ?? null,
                    due_at: $due_at->getTimestamp(),
                    message: $message,
                    status: 'new',
                    error: null,
                    discord_user: $discordUser,
                );

                // create remainder
                $this->getApiClient()->createRemainder(remainder: $newRemainder)->then(
                    onFulfilled: function (Response $response) use ($interaction, $discordUser) {

                        $remainder = (RemainderResponse::make($response))->remainder;

                        $this->getCache()->forgetRemainderList($discordUser);

                        DiscordBot::respondToInteraction(
                            interaction: $interaction,
                            template: ClientMessages::successRemainderCreated,
                            variables: [
                                'discordUser' => $discordUser,
                                'remainder' => $remainder,
                            ]
                        );

                    },
                    onRejected: DiscordBot::onPromiseRejected($interaction)
                );
            },
            onRejected: DiscordBot::onPromiseRejected($interaction)
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Generates autocomplete list.
     *
     * @param Interaction $interaction
     *
     * @return void
     *
     */
    public function autocomplete(Interaction $interaction): void
    {
        $option = $interaction->data->options->get('focused', 1);

        match ($option->name) {
            'when' => $this->autoCompleteWhen($interaction, $option),
            default => $interaction->autoCompleteResult([]),
        };

    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Generates autocomplete list for the when/due_at parameter.
     *
     * @param Interaction $interaction
     * @param RequestOption $option
     *
     * @return array
     *
     */
    protected function autoCompleteWhen(Interaction $interaction, RequestOption $option): void
    {
        $searchString = $option->value;

        $discordUser = DiscordUser::fromInteraction($interaction);

        $this->getCache()->getDiscordUser($discordUser)->then(
            onFulfilled: function (DiscordUser $discordUser) use ($interaction, $searchString): void {


                if ($this->failAutoCompleteIfTimezoneNotSet($interaction, $discordUser)) {
                    return;
                }

                $result = [];
                $due_at = 'n/a'; //NOTE: "Must be between 1 and 100 in length.", no empty string allowed...

                if ($searchString === '') {
                    // no data jet, dispay placeholder
                    $result[] = optionChoise("Start typing a time...");
                } else {
                    // try to parse the time

                    if (isDateTimeValid($searchString)) {
                        $due_at = Carbon::parse($searchString, $discordUser->timezone)->diffForHumans();
                    } else {
                        $result[] = optionChoise('Error: invalid time');
                    }

                    $result[] = optionChoise($searchString);
                    $result[] = optionChoise($due_at);
                }

                $interaction->autoCompleteResult($result);
            },
            onRejected: DiscordBot::onPromiseRejected($interaction)
        );
    }


    // --------------------------------------------------------------------------------------------------------------
    /**
     * Defines the structure of the command
     *
     * @return CommandBuilder
     *
     */
    public function getConfig(): CommandBuilder
    {
        $discord = $this->getDiscord();

        return (new CommandBuilder())
            ->setName('rem')
            ->setDescription('Sets a reminder')
            ->addOption(
                (new Option($discord))
                    ->setName('when')
                    ->setType(Option::STRING)
                    ->setDescription('The time to remind you')
                    ->setRequired(true)
                    ->setAutoComplete(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('message')
                    ->setType(Option::STRING)
                    ->setDescription('The body of the remainder')
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('channel')
                    ->setType(Option::CHANNEL)
                    ->setDescription('The channel of the remainder')
                    ->setRequired(false)
            )
        ;
    }
}
