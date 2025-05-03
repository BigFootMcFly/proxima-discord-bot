<?php

namespace Client;

use Bot\DevLogger;
use Exception;
use React\Http\Message\Response;

/**
 * Class to handle/parse API resrponse
 */
class ApiResponse
{
    public readonly int $responseCode;
    protected array $responseData;

    protected array $internalError = [];

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Creates a json ready array to be used in DevLogger
     *
     * @return array
     *
     */
    public function toJsonLogData(): array
    {
        $result = [
            'code' => $this->responseCode,
            'response' => $this->responseData,
            'errors' => [
                'internalErrors' => $this->internalError,
                'responseErrors' => (array_key_exists('errors', $this->responseData))
                    ? $this->responseData['errors']
                    : [],
            ],

        ];

        return $result;
    }

    // --------------------------------------------------------------------------------------------------------------
    public function __construct(
        public readonly Response $response
    ) {

        // get HTTP response  code
        $this->responseCode = $response->getStatusCode();

        // parse response
        try {
            $this->responseData = json_decode(json: $response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception $exception) {
            // store internal error
            $this->internalError[] = $error = [
                'type' => 'json_decode_error',
                'error_code' => $exception->getCode(),
                'error_message' => $exception->getMessage(),
                'response-code' => $this->responseCode,
                'response-data' => $response->getBody(),
            ];

            //NOTE: if needed, more details can be added here
            // log the error for the developer(s)
            DevLogger::error(
                message: "JSON decoding failed",
                context: $error,
            );
        }
    }

    // visual sugar
    // --------------------------------------------------------------------------------------------------------------
    /**
     * Instantiate a new ApiResponse object
     *
     * @param Response $response
     *
     * @return static
     *
     */
    public static function make(Response $response): static
    {
        return new static($response);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Checks if the API returns 401|Unauthorised response
     *
     * @return bool
     *
     */
    public function isUnauthenticated(): bool
    {
        return $this->responseCode === 401;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Checks if the respons has errors
     *
     * @return bool true if any errors wer found, false otherwise
     *
     */
    public function hasErrors(): bool
    {
        if (count($this->internalError) > 0) {
            return true;
        }

        if (array_key_exists('errors', $this->responseData)) {
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Checks if the response contains the specified error
     *
     * @param string $error
     *
     * @return bool true if the specified error was found, false otherwise
     *
     */
    public function hasError(string $error): bool
    {
        return array_key_exists('errors', $this->responseData)
            && array_key_exists($error, $this->responseData['errors']);
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Checks if the response has the specified path
     *
     * @param string $path Comma separated path Ex.: 'data.error.reason'
     *
     * @return bool true, if the path is present, false otherwise
     *
     */
    public function hasPath(string $path): bool
    {
        // get the list of path nodes
        $nodes = explode('.', $path);
        $current = &$this->responseData;

        // check all the nodes
        foreach ($nodes as $node) {
            if (!array_key_exists($node, $current)) {
                return false;
            }
            $current = &$current[$node];
        }

        return true;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Gets the specified path from the response
     *
     * @param string $path Comma separated path Ex.: 'data.error.reason'
     *
     * @return mixed the value at the path in the response
     *
     * @throws Exception If the specified path don't exists
     *
     */
    public function getPath(string $path): mixed
    {
        $nodes = explode('.', $path);
        $current = &$this->responseData;

        $pathErrorNode = '';
        foreach ($nodes as $node) {
            if (!array_key_exists($node, $current)) {
                $pathErrorNode .= ">>$node<<";
                throw new Exception(message: "Path ($pathErrorNode) not found", code: 404);
            }
            $pathErrorNode .= ".$node";
            $current = &$current[$node];
        }

        return $current;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Checks if the respons was a success
     *
     * It was a success, if the HTTP return code is a 2xx
     *
     * @return bool true if the response was successful, false otherwise
     *
     */
    public function success(): bool
    {

        if ($this->hasErrors()) {
            return false;
        }

        if ($this->responseCode >= 200 && $this->responseCode < 300) {
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------------------------------------------------
    /**
     * Checks if the respons was a failure
     *
     * @return bool true if the response failed, false otherwise
     *
     */
    public function failed(): bool
    {
        return !$this->success();
    }

}
