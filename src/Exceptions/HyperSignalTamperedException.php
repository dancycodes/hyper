<?php

namespace Dancycodes\Hyper\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Locked Signal Tampering Security Exception
 *
 * Exception thrown when locked signal tampering is detected during request processing.
 * Occurs when client-modified locked signal values fail cryptographic signature verification,
 * when locked signal values differ from server-stored encrypted session values, or when
 * unexpected locked signals appear in request that were not present during initialization.
 *
 * Locked signals use underscore suffix naming convention to mark server-controlled values
 * protected from client modification through encrypted session storage and hash verification.
 * Tampering detection prevents unauthorized modification of security-sensitive state like
 * user permissions, role identifiers, or resource ownership flags.
 *
 * Exception handling includes automatic security logging with user context, conditional
 * response rendering based on request type, and configurable violation reporting through
 * Laravel logging system. Hyper requests receive reactive error responses with signals
 * update, JSON requests receive structured error payload, traditional requests redirect
 * with validation error messages.
 *
 * @see \Dancycodes\Hyper\Http\HyperSignal::validateLockedSignals()
 * @see \Dancycodes\Hyper\Http\HyperSignal::storeLockedSignals()
 */
class HyperSignalTamperedException extends Exception
{
    /**
     * Initialize tampering exception with security violation details
     *
     * @param string $message Security violation description
     * @param int $code HTTP status code (default: 400 Bad Request)
     * @param \Exception|null $previous Previous exception for chaining
     */
    public function __construct(string $message = 'Signal tampering detected', int $code = 400, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render exception as HTTP response with security logging
     *
     * Logs security violation with user context and IP address when logging enabled in
     * configuration, then generates appropriate response based on request type. Hyper
     * requests receive reactive response with error signal and console warning, JSON
     * requests receive structured error payload, traditional requests redirect back
     * with error message.
     *
     * @param \Illuminate\Http\Request $request Current HTTP request
     *
     * @return mixed HTTP response appropriate for request type
     */
    public function render(\Illuminate\Http\Request $request): mixed
    {
        if (config('hyper.security.locked_signals.log_violations', true)) {
            logger()->warning('Hyper Signal Tampering Detected', [
                'message' => $this->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
        }

        /** @phpstan-ignore method.notFound (isHyper is a Request macro) */
        if ($request->isHyper()) {
            return hyper()
                ->signals(['error' => 'Security violation: Signal tampering detected'])
                ->js('console.error("🔒 Hyper Security: Signal tampering detected");')
                ->toResponse($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'Security violation detected',
                'message' => 'Signal tampering detected',
            ], 400);
        }

        return redirect()->back()->withErrors([
            'hyper_security' => 'Security violation detected. Please try again.',
        ]);
    }

    /**
     * Determine if exception should be reported to logging system
     *
     * Security violations are always reported to enable audit trail monitoring and
     * intrusion detection through log analysis. Returns true to trigger Laravel
     * exception reporting pipeline.
     *
     * @return bool True to report exception
     */
    public function report(): bool
    {
        return true;
    }
}
