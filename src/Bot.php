<?php

use function Core\discord;

const BOT_ROOT = __DIR__;
define('BOT_BUILD', trim(file_get_contents(BOT_ROOT . DIRECTORY_SEPARATOR . 'version')));

require_once __DIR__ . '/Bootstrap/Requires.php';

discord()->run(); // Run the bot
