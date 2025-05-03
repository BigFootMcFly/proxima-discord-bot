<?php

namespace Client\Models;

use Carbon\Carbon;
use Client\Responses\Loadable;

use function Core\isTimeZoneValid;

/**
 * The Remainder model
 */
class Remainder extends Loadable
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Creates a new Remainder instance.
     *
     * @param ?int $id
     * @param ?string $discord_user_id
     * @param ?string $channel_id
     * @param int|Carbon|null $due_at
     * @param ?string $message
     * @param ?string $status
     * @param ?string $error
     * @param DiscordUser|array|null $discord_user
     *
     * NOTE: the $discord_user parameter can be:
     *      null        - if not present (default)
     *      array       - if the backend API returns it with the remainder (a DiscordUser object will be instantiated)
     *      DiscordUser - if the command handler assigns an existing DiscordUser object to it
     */
    public function __construct(
        public ?int $id,
        public ?string $discord_user_id,
        public ?string $channel_id,
        public int|Carbon|null $due_at,
        public ?string $message,
        public ?string $status,
        public ?string $error,
        public DiscordUser|array|null $discord_user = null
    ) {
        // if there is a a filled array with DiscordUser properties, instantiate a new DiscorDuser
        if (is_array($discord_user)) {
            $this->discord_user = DiscordUser::makeFromArray($discord_user);
        }
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns a human readable string of the relative difference to the current time.
     *
     * @return string
     *
     * NOTE: do not remove this, it is used in the smarty template
     */
    public function humanReadable(): string
    {
        return $this->dueAtAsCarbon()->diffForHumans();
    }

    protected function dueAtAsCarbon(): Carbon
    {
        return is_a($this->due_at, 'Carbon\Carbon')
            ? $this->due_at
            : Carbon::createFromTimestamp($this->due_at);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Returns a human readable string based on the discorduser's timezone if available, otherwise defaults to UTC.
     *
     * @param DiscordUser|string $timezone=null
     *
     * @return string
     *
     * NOTE: a supplied valid timezone will be used even if the remainder defines their own!
     *       this is the intended behaviour, so the time can be shown to the viewers own timezone
     *
     */
    public function dueAt(DiscordUser|string $timezone = null): string
    {
        $defaulted = false;

        // try to find timezone
        $timezone = match (true) {
            is_a($timezone, DiscordUser::class) => $timezone->timezone,
            is_string($timezone) && isTimeZoneValid($timezone) => $timezone,
            $timezone === null && $this->discord_user !== null => $this->discord_user->timezone,
            default => false
        };

        // if timezone was not found, set as default to UTC
        if (false === $timezone) {
            $defaulted = true;
            $timezone = 'UTC';
        }

        // make sure result is a Carbon object
        $result = $this->dueAtAsCarbon();

        // apply the timezone
        $result->setTimezone($timezone);

        // append UTC for notification  in case the timezone may differ from the discorduser's timezone
        if ($defaulted) {
            $result .= ' (UTC)';
        }

        return $result;

    }

    /**
     * Checks if the DueAt time is in the past and the Remainder is not closed
     *
     * @return bool true if the remainder is not colsed and the due_at is in the past, false otherwise
     *
     */
    public function isOverDue(): bool
    {
        return
            $this->dueAtAsCarbon() < Carbon::now()
            && $this->status !== 'finished'
            && $this->status !== 'failed'
        ;
    }

}
