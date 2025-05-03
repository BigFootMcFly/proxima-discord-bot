<?php

namespace Client\Traits;

/**
 * Common methodes to help instantiate an object from properties
 *
 * This trait can be used to instantiate an object, with the parameters provided by the backend api.
 */
trait FromJson
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Instantiates an object from the class with the provided json values
     *
     * @param string $source json string provided by the backend api
     *
     * @return self|null
     *
     */
    public static function fromJson(string $source): self|null
    {
        return static::makeFromArray(json_decode(json: $source, associative: true));
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Instantiates an object from the class with the provided parameters
     * @abstract
     * @param array|null|bool $data parameter array provided by the backend api
     *
     * @return self|null
     *
     */
    abstract public static function makeFromArray(array|null|bool $data): self|null;
}
