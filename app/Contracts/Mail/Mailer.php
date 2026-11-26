<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Mail;

/**
 * Application-facing service for queueing and sending transactional email.
 *
 * Application services must call queue() so HTTP requests never block on SMTP
 * handshakes and password-reset responses keep uniform timing (SEC-A-04).
 * sendNow() is reserved for privileged diagnostics such as the Admin SMTP
 * test-email action.
 *
 * Implementations enforce outbound rate limits, localize templates via the
 * "mail" domain in the recipient's locale, and redact sensitive tokens from
 * delivery logs and dead-letter records.
 *
 * See docs/MAIL.md, docs/SECURITY_REQUIREMENTS.md (SEC-C-09) and ADR 0006.
 */
interface Mailer
{
    /**
     * Enqueue a validated message for asynchronous delivery with retries.
     *
     * @return string Stable queued job or message tracking ID.
     *
     * @throws \RuntimeException When rate limits are exceeded or the queue cannot persist the job.
     */
    public function queue(EmailMessage $message): string;

    /**
     * Deliver a validated message synchronously through the active transport.
     *
     * Intended strictly for privileged diagnostics (e.g. Admin "Send test email").
     *
     * @return string Transport message ID.
     *
     * @throws \RuntimeException On delivery failure.
     */
    public function sendNow(EmailMessage $message): string;
}
