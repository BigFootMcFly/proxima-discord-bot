<?php

namespace Bot;

use Client\ApiResponse;
use Client\ClientMessages;
use Client\Models\DiscordUser;
use Client\Template;
use Client\Traits\AssureTimezoneSet;
use Client\Traits\HasCache;
use Client\Traits\HasApiClient;
use Client\Traits\HasDiscord;
use Client\Traits\HasTemplate;
use Client\Traits\Singleton;
use Discord\Helpers\Deferred;
use Discord\Parts\Interactions\Interaction;
use Exception;
use React\Promise\PromiseInterface;

use function Core\messageWithContent;

/**
 * Helper object for the bot
 *
 * @singleton
 */
class DiscordBot
{

    use AssureTimezoneSet, Singleton, HasApiClient, HasDiscord, HasTemplate, HasCache;

    // --------------------------------------------------------------------------------------------------------------
    private function __construct()
    {
        //
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Responds to an interaction with an ansi colored message
     *
     * @param Interaction $interaction The interaction to resopnd to
     * @param string $template The smarty template to respond with
     * @param array $variables The variables for the smarty template (colors are already loaded)
     *
     * @return void
     *
     */
    public static function respondToInteraction(Interaction $interaction, string $template, array $variables = []): void
    {
        // try to respond
        try {
            $interaction->respondWithMessage(messageWithContent(Template::ansi($template, $variables)));
        } catch (Exception $exception) {

            // log the error
            DevLogger::error(
                message: 'respondToInteraction failed',
                context: [
                    'exception' => $exception,
                ],
            );
            self::failInteraction($interaction);

        }
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Sends an error back to the discord client
     *
     * @param Interaction $interaction
     * @param array $variables
     *
     * @return void
     *
     */
    public static function failInteraction(Interaction $interaction, array $variables = []): void
    {
        static::respondToInteraction(
            interaction: $interaction,
            template: ClientMessages::errorGeneralError,
            variables: $variables
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fails the interaction and sends an error back to the discord client
     *
     * @param Interaction $interaction The interaction to resopnd to
     * @param Exception $exception The exception that caused the failure
     *
     */
    public static function failApiRequestWithException(Interaction $interaction, Exception $exception)
    {
        DevLogger::warning(
            message: 'Api request failed',
            context: [
                'exception' => $exception,
            ]
        );

        static::respondToInteraction(
            interaction: $interaction,
            template: ClientMessages::errorGeneralError,
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fails the interaction and sends an error back to the discord client
     *
     * @param Interaction $interaction The interaction to resopnd to
     * @param ApiResponse $apiResponse The ApiResponse that caused the failure
     *
     */
    public static function failApiRequestWithApiResponse(Interaction $interaction, ApiResponse $apiResponse)
    {
        DevLogger::warning(
            message: 'Api request failed',
            context: [
                'response' => $apiResponse->toJsonLogData(),
            ]
        );

        static::respondToInteraction(
            interaction: $interaction,
            template: ClientMessages::errorGeneralError,
        );
    }


    // --------------------------------------------------------------------------------------------------------------
    /**
     * Sends a fail message to the discord client.
     *
     * @param Interaction $interaction The interaction with the discord client.
     * @param ApiResponse|Exception $reason The reason the api call failed.
     *
     * @return mixed This may or may not return anything.
     * NOTE: not set the return type to void so it can be used in arrow functions.
     * NOTE: this is a wrapper to simulate methode overloading.
     *
     */
    public static function failApirequest(Interaction $interaction, ApiResponse|Exception $reason)
    {
        return match (true) {
            is_a($reason, Exception::class) =>  self::failApiRequestWithException($interaction, $reason),
            is_a($reason, ApiResponse::class) => self::failApiRequestWithApiResponse($interaction, $reason),
        };
    }

    /**
     * Returns a standardised dynamic event handler for an PromiseInterface onReject event
     *
     * @param Interaction $interaction
     *
     * @return callable
     *
     */
    public static function onPromiseRejected(Interaction $interaction): callable
    {
        return fn (ApiResponse|Exception $reason) => self::failApiRequest($interaction, $reason);
    }


    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns a DiscordUsers with it's $remainder property populated
     *
     * This uses sepatare API calls for the DiscordUser and Remainder[], so they can be used/cached separatly
     *
     * @param Interaction $interaction
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @promise-fulfilled fn (DiscordUser $discordUser): void
     * @promise-rejected fn (mixed $reason): void
     *
     */
    public function getDiscordUserRemainders(Interaction $interaction, DiscordUser $discordUser): PromiseInterface
    {

        $deferred = new Deferred();

        // get the DiscordUser
        $this->getCache()->getDiscordUser($discordUser)->then(
            onFulfilled: function (DiscordUser $discordUser) use ($interaction, $deferred): void {

                // fail and send error message to the discord client if the discorduser does not have a valid timezone
                if ($this->failIfTimezoneNotSet($interaction, $discordUser)) {
                    $deferred->reject(new Exception("DiscordUser has no timezone set."));
                }

                // get the Remainders
                $this->getCache()->getRemainderList($discordUser)->then(
                    onFulfilled: function (array $reaminders) use ($discordUser, $deferred): void {
                        $discordUser->remainders = $reaminders;
                        $deferred->resolve($discordUser);
                    },
                    onRejected: DiscordBot::onPromiseRejected($interaction) // getRemainderList
                );

            },
            onRejected: DiscordBot::onPromiseRejected($interaction)  // getDiscordUser
        );

        return $deferred->promise();

    }

    /**
     * Returns the preferred dtaetime format.
     *
     * @return string the datetime format
     *
     */
    public static function getDateTimeFormat(): string
    {
        return 'Y-m-d H:i';
    }

}
