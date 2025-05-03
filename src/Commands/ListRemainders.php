<?php

namespace Commands;

use Bot\DiscordBot;
use Client\ClientMessages;
use Client\Models\DiscordUser;
use Client\Traits\AssureTimezoneSet;
use Client\Traits\HasCache;
use Client\Traits\HasDiscord;
use Core\Commands\Command;
use Core\Commands\CommandHandler;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

/**
 * The "/list" command handler.
 *
 * Lists DiscordUser remainders.
 *
 * @example /list [page] <page=1> - Shows the paginated list of remainders for the DiscordUser.
 *
 */
#[Command]
class ListRemainders implements CommandHandler
{
    use HasCache, HasDiscord, AssureTimezoneSet;

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
            onFulfilled: function (DiscordUser $discordUser) use ($interaction) {

                $pageSize = 20; // keep it low, so the message will fit in the 2000 character limit
                $itemCount = count($discordUser->remainders);
                $pageCount = match ($itemCount) {
                    0 => 1,
                    default => ceil($itemCount / $pageSize)
                };

                $page = $interaction->data->options->get('name', 'page')?->value ?? 1;

                // fail if the page is not valid
                if ($page < 1 || $page > $pageCount) {
                    DiscordBot::respondToInteraction(
                        interaction: $interaction,
                        template: ClientMessages::errorListPageInvalid,
                        variables: [
                            'page' => $page,
                            'pageCount' => $pageCount,
                        ]
                    );
                }

                // paginate  remainders
                $first = $pageSize * ($page - 1);
                $currnetRemainders = array_slice($discordUser->remainders, $first, $pageSize);

                // start counting from 1 instead of 0
                $first++;

                DiscordBot::respondToInteraction(
                    interaction: $interaction,
                    template: ClientMessages::listRemaindersCompacted,
                    variables: [
                        'discordUser' => $discordUser,
                        'remainders' => $currnetRemainders,
                        'paginate' => [
                            'pageSize' => $pageSize,
                            'pageCount' => $pageCount,
                            'page' => $page,
                            'itemCount' => $itemCount,
                            'first' => $first,
                            'last' => $first + count($currnetRemainders) - 1,
                        ],
                    ]
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
        //
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
            ->setName('list')
            ->setDescription('Lists the current reminders.')
            ->addOption(
                (new Option($this->getDiscord()))
                    ->setName('page')
                    ->setType(Option::INTEGER)
                    ->setDescription('The page to show. (defulats: 1).')
                //->setRequired(false)
                //->setAutoComplete(true)
            )
        ;
    }
}
