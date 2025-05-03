<?php

namespace Services;

use Bot\DevLogger;
use Carbon\Carbon;
use Client\ApiClient;
use Client\ApiResponse;
use Client\Models\Remainder;
use Client\Responses\RemainderListResponse;
use Client\Traits\HasApiClient;
use Client\Traits\HasDiscord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\User;
use Exception;
use React\Http\Message\Response;

use function Core\debug;
use function Core\warning;

/**
 * Fetches actual remainders and sends them to the discord api
 */
class ReminderService
{
    use HasApiClient, HasDiscord;

    // ------------------------------------------------------------------------------------------------------------
    public function __construct()
    {
        //NOTE: the discord() is not ready jet, so no logger nor debug() is available at this point
        echo "RemainderService created.\n";
    }

    /**
     * Sends a remainder to a specified channel
     *
     * @param Remainder $remainder The remainder to send
     * @param Channel $channel The channel to send the remainder to
     * @param User $user The discord user to send the remainder to
     *
     * @return void
     *
     */
    protected function sendRemainderToChannel(Remainder $remainder, Channel $channel, User $user): void
    {
        debug(sprintf('Remainder (%d) user (@%d) private channel (#%d) found', $remainder->id, $user->id, $channel->id));

        $channel->sendMessage("Remainder: $remainder->message")->then(
            onFulfilled: function ($result) use (&$remainder, $channel) {

                debug(sprintf('Remainder (%d) sent to specified channel (#%d)', $remainder->id, $channel->id));

                $this->getApiClient()->updateRemainder($remainder, ['status' => 'finished'])->then(
                    onFulfilled: function (Response $response) use ($remainder) {
                        debug(sprintf('Remainder (%d) updated as finished', $remainder->id));
                    },
                    onRejected: function (Exception $exception) use ($remainder) {
                        DevLogger::warning(
                            message: 'Api request failed',
                            context: [
                                'exception' => $exception,
                            ]
                        );
                        //TODO: use $this->onLoopReject maybe???
                        debug(sprintf('Remainder (%d) update as finished FAILED with message: "%s"', $remainder->id, $exception->getMessage()));
                    }
                );
            },
            onRejected: fn (Exception $exception) => DevLogger::warning(
                message: 'Send Message failed',
                context: [
                    'exception' => $exception,
                    'remainder' => $remainder,
                ]
            )
        );

    }

    /**
     * Sends a remainder trough the discord api
     *
     * @param Remainder $remainder The Remainder to send
     *
     */
    protected function sendRemainder(Remainder $remainder)//: PromiseInterface
    {
        // get the discord User
        $this->getDiscord()->users->fetch($remainder->discord_user->snowflake)->then(
            onFulfilled: function (User $user) use (&$remainder) {

                debug(sprintf('Remainder (%d) Discord::User (@%d) found', $remainder->id, $user->id));

                // if the remainder _DOES_NOT_ have a channel set, get a private channel to the DiscordUser
                if (null === $remainder->channel_id) {
                    $user->getPrivateChannel()->then(
                        onFulfilled: fn (Channel $channel) =>
                            $this->sendRemainderToChannel($remainder, $channel, $user),
                        onRejected: fn (Exception $exception) => DevLogger::warning(
                            message: 'Send Message ot Private channel failed',
                            context: [
                                'exception' => $exception,
                                'remainder' => $remainder,
                            ]
                        )
                    );
                } else {
                    $channel = $this->getDiscord()->getChannel($remainder->channel_id);

                    // if the channel cannot be found (maybe deleted) or inaccesible (not authorised to see it)
                    if ($channel === null) {

                        $this->getApiClient()->updateRemainder(
                            remainder: $remainder,
                            changes: ['status' => 'failed', 'error' => 'Channel not found.']
                        )->then(
                            onFulfilled: function ($result) use (&$remainder) {
                                DevLogger::warning(
                                    message: 'Remainder had an invalid channel',
                                    context: [
                                        'reaminder' => $remainder,
                                    ]
                                );
                                warning('Channel not found event detected. See dev.log for more details.');
                                return;
                            },
                            onRejected: fn (Exception $exception) => DevLogger::warning(
                                message: 'Api request failed',
                                context: [
                                    'exception' => $exception,
                                ]
                            )
                        );
                    } else {
                        $this->sendRemainderToChannel($remainder, $channel, $user);
                    }
                }
            },
            onRejected: function (Exception $exception) use (&$remainder) {
                DevLogger::warning(
                    message: 'Remainder had an invalid user snowflake',
                    context: [
                        'exception' => $exception,
                        'reaminder' => $remainder,
                    ]
                );
                warning('User not found event detected. See dev.log for more details.');
                return;
            }
        );
    }
    // ------------------------------------------------------------------------------------------------------------
    /**
     * The periodically called handler
     *
     * Retrievs the actual remainders from the backend and sends them trough the discord api
     *
     * @param mixed $timer
     *
     */
    public function onLoop($timer)
    {

        // get actual remaindres
        $this->getApiClient()->getActualRemainders()->then(
            onFulfilled: function (Response $response) {

                $actualRemainders = RemainderListResponse::make($response);

                // print debug info
                debug(
                    sprintf(
                        'Gettnig actual remainders at "%s", got %d remainder.',
                        Carbon::now('Europe/Budapest'),
                        count($actualRemainders->remainderList)
                    )
                );

                // send each remainder
                foreach ($actualRemainders->remainderList as $remiainder) {
                    $this->getApiClient()->updateRemainder($remiainder, ['status' => 'pending'])->then(
                        onFulfilled: function (Response $response) use ($remiainder) {
                            $this->sendRemainder($remiainder);
                        },
                        onRejected: $this->onLoopRejected(false)
                    );
                }

            },
            onRejected: $this->onLoopRejected(true)
        );

    }


    /**
     * Returns a function to handle Promise onReject
     *
     * Saves the reject reason to the dev log and optionally send a debug meseg to the output
     *
     * @param bool $showDebug if true, a debug message is written to the output, otherwise no message
     *
     * @return callable The function to handle the onReject callback
     *
     */
    private function onLoopRejected(bool $showDebug = false): callable
    {
        return function (Exception|ApiResponse $reason) use ($showDebug) {
            $keyName = is_a($reason, ApiClient::class) ? 'apiResponse' : 'exception';

            DevLogger::warning(
                message: 'Api request failed',
                context: [
                    $keyName => $reason,
                ]
            );

            if ($showDebug) {
                $debugMessage = sprintf(
                    'Gettnig actual remainders at "%s", failed, see dev.log for details.',
                    Carbon::now('Europe/Budapest')
                );
                debug($debugMessage);
            }
        };

    }

}
