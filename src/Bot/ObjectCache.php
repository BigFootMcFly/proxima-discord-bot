<?php

namespace Bot;

/**
 * Memory Cache for key=>value pair data
 */
class ObjectCache
{


    /**
     * @var array The cached objects
     */
    protected array $data = [];


    /**
     * Instantiates a new ObjectCache object
     *
     * @param int $ttl time to live for an item in the caches
     *
     */
    public function __construct(protected int $ttl = 30)
    {
        //
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Store the provided data
     *
     * If the key already exists, the data will be replaced
     *
     * @param mixed $key
     * @param mixed $data
     *
     * @return mixed Returns the stored object
     *
     */
    public function store(mixed $key, mixed $data): mixed
    {
        $item = new CacheItem($data, $this->ttl);
        $this->data[$key] = $item;
        return $item->getData();
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Removes the stored data from the cache
     *
     * @param mixed $key
     *
     * @return void
     *
     */
    public function forget(mixed $key): void
    {
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
        }
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Retrives the stored data from the cache
     *
     * @param mixed $key The key that was used to store the data
     *
     * @return mixed The store data if exists, null otherwise
     *
     */
    public function get(mixed $key): mixed
    {
        if (array_key_exists($key, $this->data)) {

            $item = $this->data[$key];

            if ($item->isExpired()) {
                $this->forget($key);
                return null;
            }

            return $item->getData();
        }
        return null;
    }

}
