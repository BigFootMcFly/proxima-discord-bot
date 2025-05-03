<?php

namespace Bot;

use Client\Traits\Singleton;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\IntrospectionProcessor;

/**
 * Creates a JSON log for the developer(s)
 *
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void emergency(string|\Stringable $message, array $context = [])
*/
class DevLogger
{
    use Singleton;

    protected Logger $logger;


    // --------------------------------------------------------------------------------------------------------------
    private function __construct()
    {
        $this->logger = new Logger('dev');

        $handler = new StreamHandler(BOT_ROOT . '/Storage/Logs/dev.log');
        $handler->setFormatter(new JsonFormatter());

        $this->logger->pushHandler($handler);
        $this->logger->pushProcessor(new GitProcessor());
        $this->logger->pushProcessor(new IntrospectionProcessor(skipClassesPartials: [
            __CLASS__,
            'Bot\\DiscordBot',
            'React\\Promise\\RejectedPromise',
            'React\\Promise\\Promise',
            'React\\Promise\\Deferred',
        ]));

    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Makes a log entry
     *
     * @param string $level The lavel the log should be marked
     * @param string|\Stringable $message The message to be logged
     * @param array $context All additional data to be logged
     *
     * @return void
     *
     */
    protected function log(string $level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }


    // --------------------------------------------------------------------------------------------------------------
    /**
     * Handles all the level variations and logs them
     *
     * For a list of available levels, see the docblock on top of the class
     *
     * @static
     * @param mixed $method
     * @param mixed $args
     *
     * @throws \BadMethodCallException
     *
     * @return void
     *
     */
    public static function __callStatic($method, $args): void
    {
        // get the list of available levels
        $logFunctions = array_map('strtolower', Level::NAMES);

        // log if method is valid
        if (in_array($method, $logFunctions)) {
            static::getInstance()->log($method, ...$args);
            return;
        }

        // inform the developer of a bad level call
        $message = "Bad programmer detected, a non existing function got called: \"{$method}\". Please correct the code.";
        static::getInstance()->log('critical', $message);

        throw new \BadMethodCallException($message, 404);
    }

}
