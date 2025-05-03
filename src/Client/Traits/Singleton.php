<?php

namespace Client\Traits;

/**
 * The functionality needed to use a class as a singlaton obejct.
 *
 * NOTE: to be able to ensure that only one instance can exist,
 *       a private __construct method must be defined in each class, that uses this trait!
 */
trait Singleton
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * the instance of he object
     *
     * @var ?self
     */
    protected static ?self $instance = null;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns the instance of the singleton
     *
     * @return self
     *
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Cloning this object
     *
     * Made private, so this cannot be used to cheat singletin pattern
     *
     * @return [type]
     *
     */
    private function __clone(): void
    {
        //
    }

}
