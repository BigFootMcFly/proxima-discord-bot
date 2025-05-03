<?php

namespace Client\Responses;

use Client\ApiResponse;
use Client\Models\Remainder;
use React\Http\Message\Response;

/**
 * Handles API responses for Remainder
 *
 * @see /docs#remainder-managment-POSTapi-v1-discord-users--discord_user_id--remainders
 * @see /docs#remainder-managment-PUTapi-v1-discord-users--discord_user_id--remainders--id-
 *
 */
class RemainderResponse extends ApiResponse
{
    /**
     * The instantiated Remainder object returned by the API request
     *
     * @var Remainder
     */
    public Remainder $remainder;
    /**
     * The list of all changed properties of the Remainder object
     *
     * @var array
     * [*]      The fields of the $changes array:
     *      'old'   mixed   The old value of the property
     *      'new'   mixed   The new value of the property
     */
    public array $changes = [];

    public function __construct(Response $response)
    {

        parent::__construct($response);

        if ($this->hasErrors()) {
            return;
        } // add error handling and/or reporting for this situation

        if (!$this->hasPath('data')) {
            return;
        } // add error handling and/or reporting for this situation

        $this->remainder = Remainder::makeFromArray($this->getPath('data'));

        if ($this->hasPath('changes')) {
            $this->changes = $this->getPath('changes');
        }
    }

}
