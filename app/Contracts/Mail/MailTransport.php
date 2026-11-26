<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Mail;

/**
 * Low-level delivery backend for validated outbound email messages.
 *
 * Implemented by core's native SmtpTransport (implicit TLS / mandatory STARTTLS),
 * LogTransport and NullTransport for development/testing, and optional provider
 * extensions (HTTP email APIs routed through OutboundHttpClient).
 *
 * Implementations must enforce strict TLS certificate verification, reject
 * silent TLS downgrades, never log credentials or sensitive bodies, and fail
 * closed on malformed envelopes.
 *
 * See docs/MAIL.md, docs/SECURITY_REQUIREMENTS.md (SEC-C-09) and ADR 0006.
 */
interface MailTransport
{
    /**
     * Stable transport identifier (e.g. "smtp", "log", "null").
     */
    public function name(): string;

    /**
     * Deliver one validated email message and return the transport message ID.
     *
     * @throws \RuntimeException On connection, TLS, authentication or permanent/transient delivery failure.
     */
    public function send(EmailMessage $message): string;
}
