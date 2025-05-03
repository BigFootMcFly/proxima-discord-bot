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
use DateTimeZone;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Option as RequestOption;

use function Core\isLocaleValid;
use function Core\isTimeZoneValid;

/**
 * The "/profile" command handler.
 *
 * Manages DiscordUser profile.
 *
 * @example /profile - Shows the current profile info
 * @example /profile timezone <timezone> - Updates the timezone.
 * @example /profile locale <locale> - Updates the locale.
 * @example /profile timezone <timezone> locale <locale> - Updates timezone and locale.
 *
 */
#[Command]
class Profile implements CommandHandler
{
    use HasCache, HasDiscord, AssureTimezoneSet;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Displays the current profile of the DiscordUser.
     *
     * @param Interaction $interaction
     *
     * @return void
     *
     */
    protected function showProfileInfo(Interaction $interaction): void
    {

        $discordUser = DiscordUser::fromInteraction($interaction);

        $this->getCache()->getDiscordUser($discordUser)->then(
            onFulfilled: function (DiscordUser $discordUser) use ($interaction) {

                // fail and send error message to the discord client if the discorduser does not have a valid timezone
                if ($this->failIfTimezoneNotSet($interaction, $discordUser)) {
                    return;
                }

                DiscordBot::respondToInteraction(
                    interaction: $interaction,
                    template: ClientMessages::infoProfile,
                    variables: [
                        'discordUser' => $discordUser,
                        'localTime' => $discordUser->localTime(),
                        'localeName' => locale_get_display_name($discordUser->locale ?? 'not defined', 'en'),
                    ]
                );

            },
            onRejected: DiscordBot::onPromiseRejected($interaction)
        );
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

        // Show info if no update was requested
        if ($interaction->data->options->count() == 0) {
            $this->showProfileInfo($interaction);
            return;
        }

        $discordUser = DiscordUser::fromInteraction($interaction);
        $updated = [];
        $errors = [];

        // update timezone if present
        if ($interaction->data->options->has('timezone')) {
            $timezone = $interaction->data->options->get('name', 'timezone');
            if (!isTimeZoneValid($timezone->value)) {
                $errors['timezone'] = $timezone->value;
            } else {
                $updated['timezone'] = [
                    'old' => $discordUser->timezone,
                    'new' => $timezone->value,
                ];
                $discordUser->timezone = $timezone->value;
            }
        }

        // update locale if present
        if ($interaction->data->options->has('locale')) {
            $locale = $interaction->data->options->get('name', 'locale');
            if (!isLocaleValid($locale->value)) {
                $errors['locale'] = $locale->value;
            } else {
                $updated['locale'] = [
                    'old' => $discordUser->locale,
                    'new' => $locale->value,
                    'name' => locale_get_display_name($locale->value ?? 'not defined', 'en'),
                ];
                $discordUser->locale = $locale->value;
            }
        }


        //fail if errors were found
        if (count($errors) > 0) {
            var_dump($errors);
            DiscordBot::respondToInteraction(
                $interaction,
                ClientMessages::errorUpdateProfileError,
                ['errors' => $errors]
            );
            return;
        }

        // update profile
        if (count($updated) > 0) {
            $this->getCache()->forgetDiscordUser($discordUser);
            $this->getCache()->getDiscordUser($discordUser)->then(
                onFulfilled: function (DiscordUser $discordUser) use ($interaction, $updated) {

                    $this->getCache()->storeDiscordUser($discordUser);

                    DiscordBot::respondToInteraction(
                        interaction: $interaction,
                        template: ClientMessages::successProfileUpdated,
                        variables: [
                            'discordUser' => $discordUser,
                            'localTime' => $discordUser->localTime(),
                            'updated' => $updated,
                        ]
                    );
                },
                onRejected: DiscordBot::onPromiseRejected($interaction)
            );
        }

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

        $interaction->autoCompleteResult(match ($option->name) {
            'timezone' => $this->autoCompleteTimeZone($interaction, $option),
            'locale' => $this->autoCompleteLocale($interaction, $option),
        });
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Generates autocomplete list for the timezone parameter.
     *
     * @param Interaction $interaction
     * @param RequestOption $option
     *
     * @return array
     *
     */
    protected function autoCompleteTimeZone(Interaction $interaction, RequestOption $option): array
    {
        $searchString = $option->value;

        $timezoneList = DateTimeZone::listIdentifiers();
        $matches = array_filter($timezoneList, fn (string $value) => stripos($value, $searchString) !== false);
        sort($matches);
        $matches = array_slice($matches, 0, 25);
        $result = array_map(fn (string $value) => ['name' => $value, 'value' => $value], $matches);

        return $result;

    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Generates autocomplete list for the locale parameter.
     *
     * @param Interaction $interaction
     * @param RequestOption $option
     *
     * @return array
     *
     */
    protected function autoCompleteLocale(Interaction $interaction, RequestOption $option): array
    {

        $searchString = $option->value;
        $matches = array_filter(LOCALES, fn (string $value) => stripos($value, $searchString) !== false);
        sort($matches);
        $matches = array_slice($matches, 0, 25);
        $result = array_map(fn (string $value) => ['name' => $value, 'value' => $value], $matches);

        return $result;

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
            ->setName('profile')
            ->setDescription('Manages your profile')
            ->addOption(
                (new Option($discord))
                    ->setName('timezone')
                    ->setType(Option::STRING)
                    ->setDescription('TimeZone')
                    ->setAutoComplete(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('locale')
                    ->setType(Option::STRING)
                    ->setDescription('Locale')
                    ->setAutoComplete(true)
            );
    }
}
