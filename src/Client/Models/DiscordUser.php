<?php

namespace Client\Models;

use Carbon\Carbon;
use Client\Responses\Loadable;
use Discord\Parts\Interactions\Interaction;

/**
 * The DiscordUser model
 */
class DiscordUser extends Loadable
{
    /**
     * Creates a new DiscordUser instance.
     *
     * @param ?int $id
     * @param ?string $snowflake
     * @param ?string $user_name
     * @param ?string $global_name
     * @param ?string $locale
     * @param ?string $timezone
     * @param array $remainders=[]
     *
     */
    public function __construct(
        public ?int $id,
        public ?string $snowflake,
        public ?string $user_name,
        public ?string $global_name,
        public ?string $locale,
        public ?string $timezone,
        public array $remainders = [],
    ) {
        // if there is a list of remianders, instantiate them
        if (0 !== count($remainders)) {
            $this->remainders = Remainder::collectionFromArray($remainders);
        }
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns the local time based on the timezone
     *
     * @return Carbon
     *
     */
    public function localTime(): Carbon
    {
        return Carbon::now($this->timezone ?? 'UTC');
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Creates a new instance using the data from teh interaction
     *
     * NOTE: This uses only the data available in the discord interaction object
     *
     * @param Interaction $interaction
     *
     * @return static
     *
     */
    public static function fromInteraction(Interaction $interaction): static
    {
        return new static(
            id: null,
            snowflake: $interaction->user->id,
            user_name: $interaction->user->username,
            global_name: $interaction->user->global_name,
            locale: $interaction->user?->locale,
            timezone: null
        );
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Determines if thetimezone is set
     *
     * @return bool true if the timezone is set, false otherwise
     *
     */
    public function hasTimeZone(): bool
    {
        return null !== $this->timezone;
    }

}
