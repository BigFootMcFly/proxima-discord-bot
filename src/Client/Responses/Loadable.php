<?php

namespace Client\Responses;

use Client\Traits\FromJson;
use JsonSerializable;

//TODO: maybe rename this class to some more usefull name
class Loadable implements JsonSerializable
{
    use FromJson;

    // --------------------------------------------------------------------------------------------------------------
    public function __construct(...$properties)
    {
        //
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns the properties
     *
     * @param bool $ignoreNullValues=false if true, the result will ignore null valued properties
     *
     * @return array the list op properties
     *
     */
    protected function getProperties(bool $ignoreNullValues = false): array
    {
        return match ($ignoreNullValues) {
            true => array_filter(get_object_vars($this), fn ($value) => $value !== null),
            false => get_object_vars($this)
        };
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * returns self as a json serialize ready array
     *
     * @return mixed
     *
     */
    public function jsonSerialize(): mixed
    {
        return $this->getProperties(true);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns self as a json string
     *
     * @param bool $ignoreNullValues=false if true, the result will ignore null valued properties
     *
     * @return mixed (string|false)
     *
     */
    public function toJson(bool $ignoreNullValues = false): mixed
    {
        return json_encode($this->getProperties($ignoreNullValues));
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Instantiates static from provided parameters
     *
     * NOTE: This must be defined by the descendant class
     *
     * @param array|null|bool $data
     *
     * @return self|null
     *
     */
    public static function makeFromArray(array|null|bool $data): ?self
    {
        return match ($data) {
            false => null,
            default => new static(...$data)
        };
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Created a specific object as $className object
     *
     * @param string $className
     * @param object $object
     *
     * @return mixed
     *
     */
    public static function objToClass(string $className, object $object): mixed
    {
        return unserialize(
            str_replace(
                'O:8:"stdClass"',
                sprintf('O:%d:"%s"', strlen($className), $className),
                serialize($object)
            )
        );

    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Instantiates a list of static obejcts from the provided json string
     *
     * @param string $source
     *
     * @return array
     *
     */
    public static function collectionFromJson(string $source): array
    {
        return static::collectionFromArray(json_decode(json: $source, associative: true));
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Instantiates a list of static obejcts from the provided array
     *
     * @param array|null|bool $data
     *
     * @return array
     *
     */
    public static function collectionFromArray(array|null|bool $data): array
    {
        $result = [];

        if ($data === null || $data === false) {
            return $result;
        }

        foreach ($data as $item) {
            $result[] = new static(...$item);
        }

        return $result;
    }

}
