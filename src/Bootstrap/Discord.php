<?php

use Core\Env;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Bot\DiscordBot;
use Services\ReminderService;


use function Core\debug;
use function Core\discord as d;

Env::get()->remainderService = new ReminderService();

Env::get()->bot = DiscordBot::getInstance();

Env::get()->discord = new Discord([
    'token' => Env::get()->TOKEN,
    //'intents' => Intents::getAllIntents(),
    //'intents' => Intents::DIRECT_MESSAGES,
    'intents' => 277025392640,
]);

require_once BOT_ROOT . '/Bootstrap/Events.php';

d()->on('init', static function (Discord $discord) {
    debug('Bootstrapping Commands...');
    require_once BOT_ROOT . '/Bootstrap/Commands.php';
});
