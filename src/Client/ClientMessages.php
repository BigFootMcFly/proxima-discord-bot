<?php
#cs-fixer:ignore

namespace Client;

use Client\Models\DiscordUser;
use Client\Models\Remainder;

//NOTE: the first empty line in each message are ignored, it is only here for better readability

/**
 * Stores the message templates to communicate with the discord client
 *
 * @uses Smarty
 */
class ClientMessages
{
    // --------------------------------------------------------------------------------------------------------------
    /**
     * The DiscordUser has not set it's timezone yet
     *
     * @var string
     * @category Warninig
     *
     */
    public const warningTimezoneNotset = <<<'EOL'

    {$yellow}Warning{$reset}: you're {$darkYellow}timezone{$reset} is not set.
    Please run {$darkCyan}"{$green}/profile timezone{$darkCyan}"{$reset} command to specify your timezone first!

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * The provided timezone is not valid.
     *
     * @var string
     * @category Error
     * @param string $timezone
     *
     */
    public const errorTimezoneNotValid = <<<'EOL'

    {$red}Error{$reset}: The timezone {$darkCyan}"{$red}{$timezone}{$darkCyan}"{$reset} is not a valid timezone!
    Please provide a valid timezone!

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * The provided locale is not valid.
     *
     * @var string
     * @category Error
     * @param string $locale
     *
     */
    public const errorLocaleNotValid = <<<'EOL'

    {$red}Error{$reset}: The locale {$darkCyan}"{$red}{$locale}{$darkCyan}"{$reset} is not a valid locale!
    Please provide a valid locale!

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * The provided datetime is not valid.
     *
     * @var string
     * @category Error
     * @param string $time
     *
     */
    public const errorDateTimeNotValid = <<<'EOL'

    {$red}Error{$reset}: The time {$darkCyan}"{$red}{$time}{$darkCyan}"{$reset} is not a valid datetime value!
    Please provide a valid datetime value!

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * The provided datetime is in the past.
     *
     * @var string
     * @category Error
     * @param string $time
     *
     */
    public const errorDateTimeInThePast = <<<'EOL'

    {$red}Error{$reset}: The time {$darkCyan}"{$red}{$time}{$darkCyan}"{$reset} is in the past!
    Please provide a datetime in the future when the remainder can be used!

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Displays the profile for the DiscordUser
     *
     * @var string
     * @category Info
     * @param DiscordUser $discordUser
     * @param string $localTime The local time for the user based on the DiscordUser's  timezone
     * @param string $localeName The name of the locale of the DiscordUser. Ex.: "Hungarian (Hungary)"
     */
    public const infoProfile = <<<'EOL'

    Your {$darkYellow}timezone{$reset} is: {$darkCyan}"{$green}{$discordUser->timezone}{$darkCyan}"{$reset},
    Your {$darkYellow}local time{$reset} is: {$darkCyan}"{$green}{$localTime}{$darkCyan}"{$reset}
    Your {$darkYellow}locale{$reset} is: {$darkCyan}"{$green}{$discordUser->locale|default:'n/a'} - {$localeName|default:'not defined'}{$darkCyan}"{$reset}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Remainder created succesfully
     *
     * @var string
     * @category Info|Success
     * @param DiscordUser $discordUser
     * @param Remainder $remainder
     *
     */
    public const successRemainderCreated = <<<'EOL'

    You're new remainder is created.
      {$darkYellow}Due at{$darkCyan}:{$reset} "{$green}{$remainder->due_at|carbon:{$discordUser->timezone}}{$reset}" ({$yellow}{$remainder->humanReadable()}{$reset})
      {$darkYellow}Message{$darkCyan}:{$reset} "{$green}{$remainder->message}{$reset}"

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Remainder updated succesfully
     *
     * @var string
     * @category Info|Success
     * @param DiscordUser $discordUser
     * @param Remainder $remainder
     *
     */
    public const successRemainderUpdated = <<<'EOL'

    You're remainder is updated.
      {$darkYellow}Due at{$darkCyan}:{$reset} "{$green}{$remainder->due_at|carbon:{$discordUser->timezone}}{$reset}" ({$yellow}{$remainder->humanReadable()}{$reset})
      {$darkYellow}Message{$darkCyan}:{$reset} "{$green}{$remainder->message}{$reset}"

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Shows general details of the error.
     *
     * @var string
     * @category Error
     * @param int $code The error code or HTTP response code
     * @param string $message The error description
     *
     */
    public const errorDetaiedError = <<<'EOL'

    {$red}Error{$reset}: Something went wrong...
    {$darkYellow}    Code{$darkCyan}:{$reset} {$yellow}{$code}{$reset}
    {$darkYellow} Message{$darkCyan}:{$reset} {$yellow}{$message}{$reset}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Displays the remainders of the DiscordUser
     *
     * @deprecated Use listRemaindersCompacted instead
     *
     * @var string
     * @category Info
     */
    public const listRemainders = <<<'EOL'

    {foreach $remainders as $remainder}
      {$remainder@index|string_format:"%02d"}: Due at: {$darkYellow}{$remainder->due_at|carbon:{$discordUser->timezone}}{$reset} with Message: {$darkYellow}{$remainder->message|truncate:30:"..."}{$reset}
    {foreachelse}
      No remainders found.
    {/foreach}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Displays the remainders of the DiscordUser
     *
     * @var string
     * @category Info
     * @param DiscordUser $discordUser
     * @param array $remainders
     * @param array $paginate
     *    'pageSize'    int The count of remainders to show on one page. Default: 20
     *    'pageCount'   int The count of available pages. (1 based)
     *    'page'        int The index of the current page. (1 based)
     *    'itemCount'   int The count of ALL items, (1 based)
     *    'first'       int The index of the first item (from all items) (1 based)
     *    'last'        int The index of the last item (from all items) (1 based)
     */
    public const listRemaindersCompacted = <<<'EOL'

    {if $paginate['pageCount']>1}
      Shown {$blue}{$paginate['first']}{$reset}..{$blue}{$paginate['last']}{$reset} of {$blue}{$paginate['itemCount']}{$reset} remainders, page {$blue}{$paginate['page']}{$reset} of {$blue}{$paginate['pageCount']}{$reset}:
    {/if}
    {foreach $remainders as $remainder}
    {if $remainder->isOverDue()}
    {assign var='dueAtColor' value=$darkRed}
    {else}
    {assign var='dueAtColor' value=$darkYellow}
    {/if}
      {($remainder@iteration+$paginate['first']-1)|string_format:"%02d"}: {$dueAtColor}{$remainder->due_at|carbon:{$discordUser->timezone}} {$darkCyan}- "{$green}{$remainder->message|truncate:20:"..."}{$darkCyan}"{$reset}
    {foreachelse}
    No remainders found.
    {/foreach}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Invalid page index provided.
     *
     * If the user tries to view a non-existing page.
     *
     * @var string
     * @category Error
     * @param int $page The index of the requested (non-existing) page.
     * @param int $pageCount The number of available pages (1 based)
     *
     */
    public const errorListPageInvalid = <<<'EOL'

      {$red}Error{$reset}: The page {$yellow}{$page}{$reset} is invalid!
      Please chose between {$yellow}1{$reset} and {$yellow}{$pageCount}{$reset}.

    EOL;


    // --------------------------------------------------------------------------------------------------------------
    /**
     * The template to be shown in the autocomplete list for teh remainder option.
     *
     * Used in Editremainder and RemoveRemainder to list remainders in autocomplete list.
     *
     * @deprecated Autocomplete list does not allow ansi coloring, use simple templating in code. Ex.: sprintf(...)
     *
     * @var string
     * @category Info
     * @param DiscordUser $discordUser
     * @param Remainder $remainder
     */
    public const editRemainder = <<<'EOL'

      {$index}: Due at: {$darkYellow}{$remainder->due_at|carbon:{$discordUser->timezone}}{$reset} with Message: {$darkYellow}{$remainder->message}{$reset}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * General error to be shown to the user in the discord client.
     *
     * @var string
     * @category Error
     *
     */
    public const errorGeneralError = <<<'EOL'

    {$red}Error{$reset}: Something went wrong on our side, sorry...
    {$white}Please try again later.{$reset}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Error reasons while the updating of the DiscordUser profile faild.
     *
     * @var string
     * @category Error
     * @param array $errors
     *    'timezone'    string If present the timezone is not a valid timezone
     *    'locale'      string If present the locale is not a valid locale
     */
    public const errorUpdateProfileError = <<<'EOL'

    {if isset($errors['timezone'])}
      {$red}Error{$reset}: The timezone {$darkCyan}"{$red}{$errors['timezone']}{$darkCyan}"{$reset} is not a valid timezone!
      Please provide a valid timezone!
    {/if}
    {if isset($errors['locale'])}
      {$red}Error{$reset}: The locale {$darkCyan}"{$red}{$errors['locale']}{$darkCyan}"{$reset} is not a valid locale!
      Please provide a valid locale!
    {/if}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Profile updated succesfully.
     *
     * @var string
     * @category Info|Success
     * @param DiscordUser $discordUser
     * @param string $localTime The local time for the user based on the DiscordUser's  timezone
     * @param array $updated The list of properties updated.
     *    'timezone'  array  If present the DiscordUsers timezone updated succesfully.
     *      'od'        string  The old value for the timezone.
     *      'new'       string  The new value for the timezone.
     *    'locale'    array  If present the DiscordUsers locale updated succesfully.
     *      'old'       string  The old value for the locale.
     *      'new'       string  The new value for the locale.
     *      'name'      string  The display name for the locale. Ex.: "Hungarian (Hungary)"
     *
     */
    public const successProfileUpdated = <<<'EOL'

    {if isset($updated['timezone'])}
      Your {$darkYellow}timezone{$reset} succesfully updated to {$darkCyan}"{$green}{$discordUser->timezone}{$darkCyan}"{$reset}.
      Your {$darkYellow}local time{$reset} is: {$darkCyan}"{$green}{$localTime}{$darkCyan}"{$reset}
    {/if}
    {if isset($updated['locale'])}
      Your {$darkYellow}locale{$reset} succesfully updated to {$darkCyan}"{$green}{$discordUser->locale|default:'n/a'} ({$updated['locale']['name']|default:'not defined'}){$darkCyan}"{$reset}
    {/if}

    EOL;

    // --------------------------------------------------------------------------------------------------------------
    /**
     * The provided emainderAlias (template by self::editRemainder) is invalid.
     *
     * If the user in the discord client does not chooses a valid item from the autocomplete list.
     *
     * @var string
     * @category Error
     * @param string $remainder The remainderAlias given by the user in the discord client. Ex.: "bad remainder index"
     */
    public const errorInvalidRemainderAlias = <<<'EOL'

    {$red}Error{$reset}: The remainder {$darkCyan}"{$red}{$remainder}{$darkCyan}"{$reset} is not a valid remainder!
    Please chose one from the selection list!

    EOL;

}
