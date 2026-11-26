<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Mail;

/**
 * Immutable, header-validated outbound email message.
 *
 * Rejects CR, LF, NUL and C0 control characters in every address, display name,
 * subject and header at construction time, and restricts custom headers to a
 * documented allow-list so header injection cannot reach any transport.
 *
 * See docs/MAIL.md, docs/SECURITY_REQUIREMENTS.md (SEC-C-09) and ADR 0006.
 */
final readonly class EmailMessage
{
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW = 'low';

    /**
     * Explicit allow-list of optional custom headers permitted on outbound mail.
     *
     * @var list<string>
     */
    public const ALLOWED_HEADERS = [
        'Auto-Submitted',
        'In-Reply-To',
        'List-Unsubscribe',
        'List-Unsubscribe-Post',
        'Message-ID',
        'References',
        'X-Auto-Response-Suppress',
    ];

    /**
     * @param string                $to          Validated recipient email address.
     * @param string                $subject     Single-line UTF-8 subject without control characters.
     * @param string                $textBody    Required plain-text body.
     * @param string|null           $htmlBody    Optional context-escaped HTML body.
     * @param string                $locale      Recipient locale tag (validated against allow-list).
     * @param string                $priority    One of PRIORITY_HIGH, PRIORITY_NORMAL, PRIORITY_LOW.
     * @param string|null           $toName      Optional recipient display name.
     * @param string|null           $replyTo     Optional validated Reply-To address.
     * @param array<string, string> $headers     Optional allow-listed headers.
     *
     * @throws \InvalidArgumentException When any address, header, subject or priority is invalid.
     */
    public function __construct(
        public string $to,
        public string $subject,
        public string $textBody,
        public ?string $htmlBody = null,
        public string $locale = 'en',
        public string $priority = self::PRIORITY_NORMAL,
        public ?string $toName = null,
        public ?string $replyTo = null,
        public array $headers = [],
    ) {
        self::assertValidEmail($this->to, 'to');
        self::assertSingleLine($this->subject, 'subject', true);

        if ($this->textBody === '') {
            throw new \InvalidArgumentException('Email plain-text body must not be empty.');
        }

        if (!in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_NORMAL, self::PRIORITY_LOW], true)) {
            throw new \InvalidArgumentException('Invalid email priority.');
        }

        if ($this->toName !== null) {
            self::assertSingleLine($this->toName, 'toName', false);
        }

        if ($this->replyTo !== null) {
            self::assertValidEmail($this->replyTo, 'replyTo');
        }

        foreach ($this->headers as $name => $value) {
            if (!in_array($name, self::ALLOWED_HEADERS, true)) {
                throw new \InvalidArgumentException("Email header is not allow-listed: {$name}");
            }
            self::assertSingleLine($value, "header {$name}", true);
        }
    }

    private static function assertValidEmail(string $address, string $field): void
    {
        self::assertSingleLine($address, $field, true);

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("Invalid email address in {$field}.");
        }
    }

    private static function assertSingleLine(string $value, string $field, bool $requireNonEmpty): void
    {
        if ($requireNonEmpty && trim($value) === '') {
            throw new \InvalidArgumentException("Email {$field} must not be empty.");
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new \InvalidArgumentException("Email {$field} contains disallowed control characters.");
        }
    }
}
