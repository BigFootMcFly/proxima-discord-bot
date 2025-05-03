<?php

namespace Core;

use Discord\Discord;
use Services\ReminderService;
use Tnapf\Env\Env as BaseEnv;

/**
 * @property-read string $TOKEN The authentication token provided by discord
 * @property-read string $BACKEND_TOKEN The authentication token provided by the backend
 * @property-read string $API_URL The url of the backend api endpoints
 * @property-read string $LOG_CHANNEL_ID The channel to send errors/warning/etc. messages by the bot
 * @property-read string $APPLICATION_ID The applicatin id if the bot provided by discord
 * @property-read string $CACHE_TTL The number of seconds to keep a cache item alive
 * @property Discord $discord The global Discord object
 * @property ReminderService $remainderService The periodic service that sends remainder every second
 *
 */
class Env extends BaseEnv
{
}
