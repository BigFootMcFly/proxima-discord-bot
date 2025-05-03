<?php

namespace Client;

use Client\Models\DiscordUser;
use Client\Models\Remainder;
use Client\Traits\HasDiscord;
use Client\Traits\Singleton;
use React\Http\Browser;
use React\Promise\PromiseInterface;

use function Core\env;

/**
 * Class to comunicase to the backend API
 * @singleton
 */
class ApiClient
{
    use HasDiscord, Singleton;

    /**
     * The base URL of the API backend
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * The React Browser to be used to query the backend API
     *
     * @var Browser|null
     */
    protected ?Browser $client = null;

    /**
     * The token to authorize the requests to the backend API
     *
     * @var string|null|null
     */
    protected ?string $token = null;

    // --------------------------------------------------------------------------------------------------------------
    private function __construct()
    {
        $this->token = env()->BACKEND_TOKEN;
        $this->baseUrl = env()->API_URL;

        $this->client = (new Browser(null, $this->getDiscord()->getLoop()))
            ->withBase($this->baseUrl)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', "Bearer $this->token");
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fetches DiscordUser identified by snowflake from backend.
     *
     * @see /docs#discord-user-by-snowflake-managment-GETapi-v1-discord-user-by-snowflake--discord_user_snowflake-
     *
     * @param string $snowflake
     *
     * @return PromiseInterface
     * @api-response DiscordUserResponse
     *
     */
    public function getDiscordBySnowflake(string $snowflake): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->get(
            url: "discord-user-by-snowflake/$snowflake"
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fetches DiscordUser identified by snowflake from backend.
     *
     * If the DiscordUserdoes does not exists, it will be created using the given data.
     *
     * @see /docs#discord-user-by-snowflake-managment-PUTapi-v1-discord-user-by-snowflake--snowflake-
     *
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @api-response DiscordUserResponse
     *
     */
    public function identifyDiscordUser(DiscordUser $discordUser): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->put(
            url: "discord-user-by-snowflake/$discordUser->snowflake",
            body: $discordUser->toJson(true)
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Creates a new DiscordUser using the given data on the backend.
     *
     * @see /docs#discord-user-managment-POSTapi-v1-discord-users
     *
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @api-response DiscordUserResponse
     *
     */
    public function createDiscordUser(DiscordUser $discordUser): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->post(
            url: 'discord-users',
            body: json_encode($discordUser)
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Update the specified DiscordUser on the backend.
     *
     * @see /docs#discord-user-managment-PUTapi-v1-discord-users--id-
     *
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @api-response DiscordUserResponse
     *
     */
    public function updateDiscordUser(DiscordUser $discordUser): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->put(
            url: "discord-users/$discordUser->id",
            body: json_encode($discordUser)
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Update the specified Remainder on the backend.
     *
     * @see /docs#remainder-managment-PUTapi-v1-discord-users--discord_user_id--remainders--id-
     *
     * @param Remainder $remainder
     * @param array $changes
     *
     * @return PromiseInterface
     * @api-response RemainderResponse
     *
     */
    public function updateRemainder(Remainder $remainder, array $changes): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->put(
            url: "discord-users/$remainder->discord_user_id/remainders/$remainder->id",
            body: json_encode($changes)
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Create a new Remainder on the backend.
     *
     * @see /docs#remainder-managment-POSTapi-v1-discord-users--discord_user_id--remainders
     *
     * @param Remainder $remainder
     *
     * @return PromiseInterface
     * @api-response RemainderResponse
     *
     */
    public function createRemainder(Remainder $remainder): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->post(
            url: "discord-users/$remainder->discord_user_id/remainders",
            body: json_encode($remainder)
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Remove the specified Remainder on the backend.
     *
     * @see /docs#remainder-managment-DELETEapi-v1-discord-users--discord_user_id--remainders--id-
     *
     * @param DiscordUser $discordUser
     * @param Remainder $remainder
     *
     * @return PromiseInterface
     * @api-response <empty>
     *
     */
    public function deleteRemainder(DiscordUser $discordUser, Remainder $remainder): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->delete(
            url: "discord-users/$remainder->discord_user_id/remainders/$remainder->id",
            body: json_encode([
                'snowflake' => $discordUser->snowflake,
            ])
        );
    }


    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fetches all the "actual" reaminders for the given second.
     *
     * @see /docs#remainder-by-dueat-managment-GETapi-v1-remainder-by-due-at--due_at-
     *
     * @return PromiseInterface
     * @api-response RemainderListResponse
     *
     */
    public function getActualRemainders(): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->get(
            url: 'remainder-by-due-at/' . time() . '?withDiscordUser'
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Fetches the Remainders for the DiscordUser from the backend.
     *
     * @see /docs#remainder-managment-GETapi-v1-discord-users--discord_user_id--remainders
     * @endpoint  GET api/v1/discord-users/{discord_user_id}/remainders
     *
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @api-response RemainderListResponse
     *
     */
    public function getRemainders(DiscordUser $discordUser): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(true)->get(
            url: "discord-users/$discordUser->id/remainders"
        );
    }


}
