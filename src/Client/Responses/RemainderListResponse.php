<?php

namespace Client\Responses;

use Client\ApiResponse;
use Client\Models\Remainder;
use React\Http\Message\Response;

/**
 * Handles API responses for Remainder lists
 *
 * @see /docs#remainder-managment-GETapi-v1-discord-users--discord_user_id--remainders
 * @see /docs#remainder-by-dueat-managment-GETapi-v1-remainder-by-due-at--due_at-
 */
class RemainderListResponse extends ApiResponse
{
    /**
     * The list of instantiated Remainder objects
     *
     * @var array
     */
    public array $remainderList;

    public function __construct(Response $response)
    {

        parent::__construct($response);

        if ($this->hasErrors()) {
            return;
        } // NOTE: add error handling and/or reporting for this situation

        if (!$this->hasPath('data')) {
            return;
        } // NOTE: add error handling and/or reporting for this situation

        $this->remainderList = Remainder::collectionFromArray($this->getPath('data'));

    }

    /**
     * Searches the Remainder by id
     *
     * @param int $id The ID to search for
     *
     * @return Remainder|null returns the Remainder if found, null otherwise
     *
     */
    public function remainderById(int $id): ?Remainder
    {
        foreach ($this->remainderList as $remainder) {
            if ($remainder->id === $id) {
                return $remainder;
            }
        }
        return null;
    }

}
