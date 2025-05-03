<?php

namespace Bot;

/**
 * Cache item to store data with expiration date
 */
class CacheItem
{

    /**
     * @var int the unix timestamp when the item was created
     */
    protected int $created;

    /**
     * @var int the unix timestamp when the item will expire
     */
    protected int $expires;

    /**
     * [Description for __construct]
     *
     * @param  mixed $data the data to store
     * @param  int $ttl the 'time to live' interval for the data in seconds
     *
     */
    public function __construct(
        protected mixed $data,
        protected int $ttl
    ) {
        $this->created = time();
        $this->expires = $this->created + $this->ttl;
    }

    /**
     * Resets the time of expiration to current time + ttl
     *
     * @return void
     *
     */
    public function refresh(): void
    {
        $this->expires = time() + $this->ttl;
    }

    /**
     * Checks if the data is expired
     *
     * @return bool true, if the data is expired, false otherwise
     *
     */
    public function isExpired(): bool
    {
        return time() > $this->expires;
    }

    /**
     * Returns the remaining ttl of the data
     *
     * @return int the seconds until the data expires
     *
     */
    public function ttl(): int
    {
        return $this->expires - time();
    }

    /**
     * Returns the stored data
     *
     * @return mixed
     *
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
