<?php

namespace Commands;

use Bot\DiscordBot;
use Carbon\Carbon;
use Client\ClientMessages;
use Client\Models\DiscordUser;
use Client\Responses\RemainderResponse;
use Client\Traits\AssureTimezoneSet;
use Client\Traits\HasCache;
use Client\Traits\HasApiClient;
use Client\Traits\HasDiscord;
use Client\Traits\HasTemplate;
use Client\Traits\RemainderListCommand;
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
 * The "/edit" command handler.
 *
 * Edits a Remainder for the DiscordUser.
 *
 * @example /edit remainder <remainder> [when] <when> [message] <message> [channel] <channel> - Edit a remainder.
 *
 */
#[Command]
class EditRemainder implements CommandHandler
{
    use
        AssureTimezoneSet,
        HasCache,
        HasApiClient,
        HasDiscord,
        HasTemplate,
        RemainderListCommand;

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

        $discordUser = DiscordUser::fromInteraction($interaction);

        DiscordBot::getInstance()->getDiscordUserRemainders($interaction, $discordUser)->then(
            onFulfilled: function (DiscordUser $discordUser) use ($interaction): void {

                // get the remainder to edit
                $remainder = $this->getActualRemainder($interaction, $discordUser->remainders);

                //fail if the actual remainder cannot be evaulated
                if (false === $remainder) {
                    $remainderAlias = $interaction->data->options->get('name', 'remainder')->value;
                    $this->failInvalidRemainderAlias($interaction, $remainderAlias);
                    return;
                }

                // get the option values
                $when = $interaction->data->options->get('name', 'when')?->value;
                $message = $interaction->data->options->get('name', 'message')?->value;
                $channel = $interaction->data->options->get('name', 'channel')?->value;

                $changes = [];

                // fail if when/due_at was provided, but is invalid
                if ($when && !isDateTimeValid($when)) {
                    DiscordBot::respondToInteraction(
                        interaction: $interaction,
                        template: ClientMessages::errorDateTimeNotValid,
                        variables: [
                            'time' => $when,
                        ]
                    );
                    return;
                }

                // if when/due_at was provided, update it
                if ($when) {
                    $changes['due_at'] = Carbon::parse($when, $discordUser->timezone)->getTimestamp();

                    // fail if the new time is already past
                    if (Carbon::now()->getTimestamp() >= $changes['due_at']) {
                        DiscordBot::respondToInteraction(
                            interaction: $interaction,
                            template: ClientMessages::errorDateTimeInThePast,
                            variables: [
                                'time' => $when,
                            ]
                        );
                        return;
                    }
                }

                // if message was provided, update it
                if ($message) {
                    $changes['message'] = $message;
                }

                // if channel was provided, update it
                if ($channel) {
                    $changes['channel_id'] = $channel;
                }

                // update the remiander
                $this->getApiClient()->updateRemainder($remainder, $changes)->then(
                    onFulfilled: function (Response $response) use ($interaction, $discordUser) {

                        $remainder = (RemainderResponse::make($response))->remainder;

                        $this->getCache()->forgetRemainderList($discordUser);

                        DiscordBot::respondToInteraction(
                            interaction: $interaction,
                            template: ClientMessages::successRemainderUpdated,
                            variables: [
                                'discordUser' => $discordUser,
                                'remainder' => $remainder,
                            ]
                        );

                    },
                    onRejected: DiscordBot::onPromiseRejected($interaction) // updateRemainder
                );

            }
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

        $discordUser = DiscordUser::fromInteraction($interaction);

        DiscordBot::getInstance()->getDiscordUserRemainders($interaction, $discordUser)->then(
            onFulfilled: function (DiscordUser $discordUser) use ($interaction, $option): void {

                $parameters = [$interaction, $option, $discordUser];

                // fill the lkist for the specified option
                match ($option->name) {
                    'remainder' => $this->autoCompleteRemainder(...$parameters),
                    'when' => $this->autoCompleteWhen(...$parameters),
                    'message' => $this->autoCompleteMessage(...$parameters),
                    default => $interaction->autoCompleteResult([]),
                };

            }
        );

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
    protected function autoCompleteWhen(Interaction $interaction, RequestOption $option, DiscordUser $discordUser): void
    {
        $searchString = $option->value;

        $result = [];
        $timezone = $discordUser->timezone;
        $remainder = $this->getActualRemainder($interaction, $discordUser->remainders);

        // fail, if the remainder cannot be evaluated
        if (false === $remainder) {
            $this->invalidRemainderAlias($interaction);
            return;
        }

        // set the current value es default
        if ($searchString == '') {
            $searchString = Carbon::createFromTimestamp($remainder->due_at)
                ->setTimezone($timezone)
                ->format(DiscordBot::getDateTimeFormat());
        }

        // fill the human readable value or show an error in case of an invalid value
        $due_at = isDateTimeValid($searchString)
            ? Carbon::parse($searchString, $timezone)->diffForHumans()
            : 'Error: invalid time';

        // add values to result list
        $result[] = optionChoise($searchString);
        $result[] = optionChoise($due_at);

        //send autocomplete results
        $interaction->autoCompleteResult($result);

    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Generates autocomplete list for the message parameter.
     *
     * @param Interaction $interaction
     * @param RequestOption $option
     *
     * @return array
     *
     */
    protected function autoCompleteMessage(Interaction $interaction, RequestOption $option, DiscordUser $discordUser): void
    {
        $searchString = $option->value;

        $result = [];
        $remainder = $this->getActualRemainder($interaction, $discordUser->remainders);

        // fail, if the remainder cannot be evaluated
        if (false === $remainder) {
            $this->invalidRemainderAlias($interaction);
            return;
        }

        if ($searchString == '') {
            $searchString = $remainder->message;
        }

        $result[] = optionChoise($searchString);

        $interaction->autoCompleteResult($result);

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
        return (new CommandBuilder())
            ->setName('edit')
            ->setDescription('Edit a reminder.')
            ->addOption(
                (new Option($this->getDiscord()))
                    ->setName('remainder')
                    ->setType(Option::STRING)
                    ->setDescription('The reminder to edit.')
                    ->setRequired(true)
                    ->setAutoComplete(true)
            )
            ->addOption(
                (new Option($this->getDiscord()))
                    ->setName('when')
                    ->setType(Option::STRING)
                    ->setDescription('The time to remind you')
                    ->setAutoComplete(true)
                    ->setRequired(false)
            )
            ->addOption(
                (new Option($this->getDiscord()))
                    ->setName('message')
                    ->setType(Option::STRING)
                    ->setDescription('The body of the remainder')
                    ->setAutoComplete(true)
                    ->setRequired(false)
            )
            ->addOption(
                (new Option($this->getDiscord()))
                    ->setName('channel')
                    ->setType(Option::CHANNEL)
                    ->setDescription('The channel of the remainder')
                    ->setRequired(false)
            )
        ;
    }
}
