<?php

namespace Events;

use Core\Events\Init;
use Discord\Discord;
use Discord\Builders\MessageBuilder;

use function Core\debug;
use function Core\env;

/**
 * Event handler for the "Ready" event
 */
class Ready implements Init
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Handles the event
     *
     * @param Discord $discord
     *
     * @return void
     *
     */
    public function handle(Discord $discord): void
    {
        debug("Bot is ready!");

        $logChannelID = env()->LOG_CHANNEL_ID;
        $appID = env()->APPLICATION_ID;
        $appVersion = BOT_BUILD;
        $logChannel = $discord->getChannel($logChannelID);

        // create start notice
        $message = MessageBuilder::new()
            ->setContent("<@$appID>(v$appVersion) ONLINE.\n")
            ->setAllowedMentions([
                'parse' => ['users'],
            ]);

        // send start notice to the log channel
        if ($logChannel !== null) {
            $logChannel->sendMessage($message);
        } else {
            debug('LOG_CHANNEL is null ('. $logChannelID .')');
        }

        // regiter RemainderService handler
        $loop = $discord->getLoop();
        debug('Registering onLoop event handler');
        $timer = $loop->addPeriodicTimer(1, [env()->remainderService, 'onLoop']);
        debug('Registered onLoop event handler');

    }
}
