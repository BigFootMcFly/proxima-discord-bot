<?php

namespace Client\Responses;

use Client\ApiResponse;
use Client\Models\DiscordUser;
use React\Http\Message\Response;

/**
 * Handles API responses for DiscordUser
 *
 * @see /docs#discord-user-by-snowflake-managment-GETapi-v1-discord-user-by-snowflake--discord_user_snowflake-
 * @see /docs#discord-user-by-snowflake-managment-PUTapi-v1-discord-user-by-snowflake--snowflake-
 * @see /docs#discord-user-managment-POSTapi-v1-discord-users
 * @see /docs#discord-user-managment-GETapi-v1-discord-users--id-
 * @see /docs#discord-user-managment-PUTapi-v1-discord-users--id-
 */
class DiscordUserResponse extends ApiResponse
{
    /**
     * The instantiated DiscordUser object returned by the API request
     *
     * @var DiscordUser
     */
    public DiscordUser $discordUser;

    public function __construct(Response $response)
    {
        parent::__construct($response);

        if ($this->hasErrors()) {
            return;
        } // add error handling and/or reporting for this situation

        if (!$this->hasPath('data')) {
            return;
        } // add error handling and/or reporting for this situation

        $this->discordUser = DiscordUser::makeFromArray($this->getPath('data'));

    }

}
