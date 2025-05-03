<?php

namespace Bot;

use Client\ApiClient;
use Client\Models\DiscordUser;
use Client\Responses\DiscordUserResponse;
use Client\Responses\RemainderListResponse;
use Client\Traits\Singleton;
use Discord\Helpers\Deferred;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;

use function Core\env;

/**
 * Memory Cache Object
 */
class Cache
{
    use Singleton;

    /**
     * @var ObjectCache DiscordUser cache
     */

    protected ObjectCache $discordUsers;
    /**
     * @var ObjectCache Remainder[] cache
     */

    protected ObjectCache $remainderLists;

    public function __construct()
    {
        $this->discordUsers = new ObjectCache(env()->CACHE_TTL);
        $this->remainderLists = new ObjectCache(env()->CACHE_TTL);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Retrives the DiscordUser object from the cache
     *
     * If the DiscordUser object is in the cache, returns it,
     * otherwise fetches it from the backand beforhand, and then returns it
     *
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @promise-fulfilled fn (DiscordUser $discordUser): void
     * @promise-rejected fn (mixed $reason): void
     *
     */
    public function getDiscordUser(DiscordUser $discordUser): PromiseInterface
    {
        $deferred = new Deferred();

        $result = $this->discordUsers->get($discordUser->snowflake);

        // if it is already in cache, return it
        if (null !== $result) {
            $deferred->resolve($result);
            return $deferred->promise();
        }

        //not in cache, request it from the backend and cache it
        ApiClient::getInstance()->identifyDiscordUser($discordUser)->then(
            onFulfilled: function (Response $response) use ($deferred): void {
                $apiResponse = DiscordUserResponse::make($response);

                $this->storeDiscordUser($apiResponse->discordUser);

                $deferred->resolve($apiResponse->discordUser);
            },
            onRejected: fn ($error) => $deferred->reject($error)
        );

        return $deferred->promise();
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Stores the DiscordUser obect in the cache
     *
     * @param DiscordUser $discordUser
     *
     * @return DiscordUser The stored DiscordUser object
     *
     */
    public function storeDiscordUser(DiscordUser $discordUser): DiscordUser
    {
        return $this->discordUsers->store($discordUser->snowflake, $discordUser);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Removes the DiscordUser object from the cache
     *
     * @param DiscordUser $discordUser
     *
     * @return void
     *
     */
    public function forgetDiscordUser(DiscordUser $discordUser): void
    {
        $this->discordUsers->forget($discordUser->snowflake);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Retrives the Remainder[] array from the cache
     *
     * @param DiscordUser $discordUser
     *
     * @return PromiseInterface
     * @promise-fulfilled fn (array $remainders): void
     * @promise-rejected fn (mixed $reason): void
     *
     */
    public function getRemainderList(DiscordUser $discordUser): PromiseInterface
    {
        $deferred = new Deferred();

        $result = $this->remainderLists->get($discordUser->snowflake);

        // if it is already in cache, return it
        if (null !== $result) {
            $deferred->resolve($result);
            return $deferred->promise();

        }
        //not in cache, request it from the backend and cache it
        ApiClient::getInstance()->getRemainders($discordUser)->then(
            onFulfilled: function (Response $response) use ($deferred, $discordUser): void {
                $apiResponse = RemainderListResponse::make($response);

                $this->storeRemainderList($discordUser, $apiResponse->remainderList);

                $deferred->resolve($apiResponse->remainderList);
            },
            onRejected: fn ($error) => $deferred->reject($error)
        );

        return $deferred->promise();
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Stores the Remaindre[] array in the cache
     *
     * @param DiscordUser $discordUser
     * @param array $remainderList
     *
     * @return array The stored Reaminder[] list
     *
     */
    public function storeRemainderList(DiscordUser $discordUser, array $remainderList): array
    {
        return $this->remainderLists->store($discordUser->snowflake, $remainderList);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Removes the Remainder[] array from the cache
     *
     * @param DiscordUser $discordUser
     *
     * @return void
     *
     */
    public function forgetRemainderList(DiscordUser $discordUser): void
    {
        $this->remainderLists->forget($discordUser->snowflake);
    }

}
