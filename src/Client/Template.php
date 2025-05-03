<?php

namespace Client;

use Bot\DiscordBot;
use Carbon\Carbon;
use Client\Traits\Singleton;
use Smarty\Data;
use Smarty\Smarty;

/**
 * Class to compile text templates
 * @singleton
 */
class Template
{
    use Singleton;

    protected Smarty $smarty;

    protected ?Data $colorData;

    /**
     * Ansi color sequences
     *
     * @var array
     */
    protected array $colors = [
        'black'       => "\033[0;30m",
        'red'         => "\033[1;31m",
        'green'       => "\033[1;32m",
        'yellow'      => "\033[1;33m",
        'blue'        => "\033[1;34m",
        'magenta'     => "\033[1;35m",
        'cyan'        => "\033[1;36m",
        'white'       => "\033[1;37m",
        'gray'        => "\033[0;37m",
        'darkRed'     => "\033[0;31m",
        'darkGreen'   => "\033[0;32m",
        'darkYellow'  => "\033[0;33m",
        'darkBlue'    => "\033[0;34m",
        'darkMagenta' => "\033[0;35m",
        'darkCyan'    => "\033[0;36m",
        'darkWhite'   => "\033[0;37m",
        'darkGray'    => "\033[1;30m",
        'bgBlack'     => "\033[40m",
        'bgRed'       => "\033[41m",
        'bgGreen'     => "\033[42m",
        'bgYellow'    => "\033[43m",
        'bgBlue'      => "\033[44m",
        'bgMagenta'   => "\033[45m",
        'bgCyan'      => "\033[46m",
        'bgWhite'     => "\033[47m",
        'bold'        => "\033[1m",
        'italics'     => "\033[3m",
        'reset'       => "\033[0m",
    ];


    // --------------------------------------------------------------------------------------------------------------
    private function __construct()
    {
        // initialize template engine
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir(BOT_ROOT . '/Storage/Smarty/templates');
        $this->smarty->setConfigDir(BOT_ROOT . '/Storage/Smarty/config');
        $this->smarty->setCompileDir(BOT_ROOT . '/Storage/Smarty/templates_c');
        $this->smarty->setCacheDir(BOT_ROOT . '/Storage/Smarty/cache');

        // create color data container
        $this->colorData = $this->smarty->createData();
        foreach ($this->colors as $key => $value) {
            $this->colorData->assign($key, $value);
        }

        // add carbon modifier
        $this->smarty->registerPlugin(
            type: Smarty::PLUGIN_MODIFIER,
            name: 'carbon',
            callback: fn (string $date, string $timeZone = null): string
                => Carbon::createFromTimestamp($date)
                    ->setTimezone($timeZone)
                    ->format(DiscordBot::getDateTimeFormat())
        );

    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Compiles and  returns the template as an ansi sequence
     *
     * @param string $stringTemplate
     * @param array $variables
     *
     * @return string
     *
     */
    public function fetchAnsi(string $stringTemplate, array $variables = []): string
    {
        // create template
        $template = $this->smarty->createTemplate(
            template_name: 'string:' . $stringTemplate,
            parent: $this->colorData,
        );

        // assign variables to the template
        foreach ($variables as $key => $value) {
            $template->assign($key, $value);
        }

        // compile template
        $result = $template->fetch();

        $result = "```ansi\n{$result}\n```";

        return $result;
    }

    /**
     * Static wrapper for the fetchAnsi() function
     *
     * @param string $template
     * @param array $variables
     *
     * @return string
     *
     */
    public static function ansi(string $template, array $variables = []): string
    {
        return static::getInstance()->fetchAnsi($template, $variables);
    }

}
