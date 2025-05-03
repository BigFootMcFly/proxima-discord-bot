<?php

use Core\Env;

//NOTE: remove comment lines from the .env file
$rawEnvFileContents = file_get_contents(BOT_ROOT . '/.env');
$filteredEnvFileContents = preg_replace('/^#.*$/m', '', $rawEnvFileContents);

$env = Env::createFromString($filteredEnvFileContents);



if (!isset($env->TOKEN)) {
    throw new RuntimeException('No token supplied to environment!');
}
