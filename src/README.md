![Latest](https://proxima.goliath.hu/proxima/discord-bot/actions/workflows/latest.yaml/badge.svg?branch=main)
![Testing](https://proxima.goliath.hu/proxima/discord-bot/actions/workflows/testing.yaml/badge.svg?branch=dev)

![Proxima Discord bot](../res/logo.svg)


## The source code of the bot.

- [Bootstrap](Bootstrap)<br>
    The startup files to boot the application.

- [Bot](Bot)
    - [Cache.php](Bot/Cache.php), [CacheItem.php](Bot/CacheItem.php), [ObjectCache.php](Bot/ObjectCache.php)<br>
        Minimal caching to minimize API calls to the backend.

    - [DevLogger.php](Bot/DevLogger.php)<br>
        Helper class to log "impossible" events, that should not happen.
        The log is written in [JSON](https://www.json.org/) format in the `/app/Bot/Storag/Logs/dev.log` file.

- [Client](Client)<br>
    The helper classes to communicate with the backend and the discord client

    - [Models](Client/Models)<br>
        The data models

    - [Responses](Client/Responses)<br>
        The responses from the backend

    - [Traits](Client/Traits)<br>
        Commonly used classes

    - [ApiClient.php](Client/ApiClient.php)<br>
        The main class to manage all user data and communication with both ends.

    - [ApiResponse.php](Client/ApiResponse.php)<br>
        Helper class to manage all the communication in one place.

    - [ClientMessages.php](Client/ClientMessages.php)<br>
        The message templates sent to the discord client. Uses the [Smarty](https://www.smarty.net/) template engine.

    - [Template.php](Client/Template.php)<br>
        Minimal template "engine" to generate [ANSI](https://gist.github.com/kkrypt0nn/a02506f3712ff2d1c8ca7c9e0aed7c06) colored messages for the discord client.

- [Commands](Commands)<br>
    The classes to handle [slash command](https://discord.com/developers/docs/tutorials/upgrading-to-application-commands)s from the discord client.

    - [CreateRemainder.php](Commands/CreateRemainder.php)<br>
        The `/rem <when> <message> (channel)` command to create a new remainder

    - [EditRemainder.php](Commands/EditRemainder.php)<br>
        The `/edit <remainder> (when) (message) (channel)` command to create a new remainder

    - [ListRemainders.php](Commands/ListRemainders.php)<br>
        The `/list (page)` command to show a paginated list of the current remainders

    - [Profile.php](Commands/Profile.php)<br>
        The `/profile (timezone) (locale)` command to display/modify the actual users profile

    - [RemoveRemainder.php](Commands/RemoveRemainder.php)<br>
        The `/delete <remainder>` command to remove a remainder (needs confirmation)

- [Core](Core)<br>
    The core components of the [commandstring/dphp-bot](https://github.com/CommandString/discordphp-bot-template) package

- [Events](Events)<br>
    The main event handling for the discord client

    - [Message.php](Events/Ready.php)<br>
        Handles all messages comming from the discord client<br>
        ***NOTE: currentky no custom handling is done here***

    - [Ready.php](Events/Ready.php)<br>
        Starts the main remainder pull service when the discord server becomes ready


- [Services](Services)<br>
    The main services to handle background tasks

    - [ReminderService.php](Services/ReminderService.php)<br>
        Periodically pulls actual remainder from the backend and sends remainders to the discord client

- [Storage](Storage)<br>
    Stores temporary files and program logs

- [Test](Test)<br>
    A rather scarce list of test, the function testings is handled by an outside service currently

- [.env.example](.env.example)<br>
    The sample configuration file to be filled before deploying the bot

- [Bot.php](Bot.php)<br>
    The main entrypoint for the bot

- [BotDev.php](BotDev.php)<br>
    Main entripoint if not run in container