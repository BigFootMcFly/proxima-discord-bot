<?php

namespace Commands;

use Bot\DiscordBot;
use Client\Models\DiscordUser;
use Client\Traits\AssureTimezoneSet;
use Client\Traits\HasCache;
use Client\Traits\HasApiClient;
use Client\Traits\HasDiscord;
use Client\Traits\HasTemplate;
use Client\Traits\RemainderListCommand;
use Core\Commands\Command;
use Core\Commands\CommandHandler;
use Discord\Builders\CommandBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Exception;

/**
 * The "/delete" command handler.
 *
 * Lists DiscordUser remainders.
 *
 * @example /list - Shows the remainders of the DiscordUser.
 *
 */
#[Command]
class RemoveRemainder implements CommandHandler
{
    use HasApiClient, HasCache, HasDiscord, HasTemplate, AssureTimezoneSet, RemainderListCommand;

    // --------------------------------------------------------------------------------------------------------------
    protected function btnCancelListener(
        Interaction $interaction,
        DiscordUser $discordUser,
        MessageBuilder $messageBuilder,
        ActionRow $actionRow,
    ): callable {
        return fn (Interaction $iAnswer2) =>
            $interaction->updateOriginalResponse($messageBuilder
                ->setContent('Kept reaminder.')
                ->removeComponent($actionRow))
                ->otherwise(DiscordBot::onPromiseRejected($interaction));
        ;
    }

    // --------------------------------------------------------------------------------------------------------------
    protected function btnOkListener(
        Interaction $interaction,
        DiscordUser $discordUser,
        MessageBuilder $messageBuilder,
        ActionRow $actionRow,
    ): callable {
        return function (Interaction $iAnswer) use ($interaction, $discordUser, $messageBuilder, $actionRow) {

            $remainder = $this->getActualRemainder($interaction, $discordUser->remainders);

            $this->getApiClient()->deleteRemainder($discordUser, $remainder)->then(
                onFulfilled: function ($data) use ($interaction, $discordUser, $iAnswer, $messageBuilder, $actionRow, $remainder): void {
                    $this->getCache()->forgetRemainderList($discordUser);

                    // update client message
                    $interaction->updateOriginalResponse($messageBuilder
                        ->setContent('Remainder deleted succesfully.')
                        ->removeComponent($actionRow))
                        ->otherwise(DiscordBot::onPromiseRejected($interaction));

                },
                onRejected: function (Exception $exception) use ($iAnswer, $interaction) {

                    $interaction->deleteOriginalResponse();
                    DiscordBot::failApiRequestWithException($iAnswer, $exception);
                }
            );
        };

    }

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

                $remainder = $this->getActualRemainder($interaction, $discordUser->remainders);
                //fail if the actual remainder cannot be evaulated
                if (false === $remainder) {
                    $remainderAlias = $interaction->data->options->get('name', 'remainder')->value;
                    $this->failInvalidRemainderAlias($interaction, $remainderAlias);
                    return;
                }

                // create message handlers
                $messageBuilder = MessageBuilder::new();
                $actionRow = ActionRow::new();

                // add OK button
                $btnOK = Button::new(Button::STYLE_SUCCESS)
                    ->setLabel('Yes, delete the remainder!')
                    ->setEmoji('👎')
                    ->setListener($this->btnOkListener(
                        interaction: $interaction,
                        discordUser: $discordUser,
                        messageBuilder: $messageBuilder,
                        actionRow: $actionRow,
                    ), $this->getDiscord())
                ;

                // add CANCEL button
                $btnCancel = Button::new(Button::STYLE_DANGER)
                    ->setLabel('No, keep the remainder.')
                    ->setEmoji('👍')
                    ->setListener($this->btnCancelListener(
                        interaction: $interaction,
                        discordUser: $discordUser,
                        messageBuilder: $messageBuilder,
                        actionRow: $actionRow,
                    ), $this->getDiscord())
                ;

                $actionRow->addComponent($btnOK)->addComponent($btnCancel);

                // send temporary response
                //TODO: maybe test for success/failure here as well...
                $interaction->acknowledgeWithResponse(true)->done(function () use ($interaction, $messageBuilder, $actionRow) {
                    $interaction->updateOriginalResponse(
                        builder: $messageBuilder
                            ->setContent('Are you sure you want to delete this remainder?')
                            ->addComponent($actionRow)
                    )->otherwise(DiscordBot::onPromiseRejected($interaction));

                });

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

                // fill the list for the specified option
                match ($option->name) {
                    'remainder' => $this->autoCompleteRemainder(...$parameters),
                    default => $interaction->autoCompleteResult([]),
                };

            }
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
        return (new CommandBuilder())
            ->setName('delete')
            ->setDescription('Delete a reminder.')
            ->addOption(
                (new Option($this->getDiscord()))
                    ->setName('remainder')
                    ->setType(Option::STRING)
                    ->setDescription('The reminder to delete.')
                    ->setRequired(true)
                    ->setAutoComplete(true)
            )
        ;
    }


}

//👍 ☠ 👎
