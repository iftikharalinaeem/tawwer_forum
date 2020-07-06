<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Web\Data;
use Garden\Web\RequestInterface;

/**
 * Middleware for applying a consistent transcationID for logs across a single request.
 */
class LogTransactionMiddleware {

    const HEADER_NAME = 'x-log-transaction-id';

    /** @var int|null */
    private $transactionID = null;

    /**
     * Invoke the cache control middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $transactionID = $request->getHeader(self::HEADER_NAME) ?: null;

        if ($transactionID !== null) {
            $intTransactionID = filter_var($transactionID, FILTER_VALIDATE_INT);
            if ($intTransactionID !== false) {
                $this->setTransactionID($intTransactionID);
            }
        }

        $response = Data::box($next($request));
        return $response;
    }

    /**
     * @return int
     */
    public function getTransactionID(): ?int {
        return $this->transactionID;
    }

    /**
     * @param int|null $transactionID
     */
    public function setTransactionID(?int $transactionID): void {
        $this->transactionID = $transactionID;
    }
}
