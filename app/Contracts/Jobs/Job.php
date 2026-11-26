<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Jobs;

/**
 * Immutable background job envelope stored in the database jobs table (CORE-013).
 *
 * Never store plaintext secrets or raw passwords inside $payload; auth mail
 * jobs store minimal encrypted or short-lived references purged on delivery.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/MAIL.md.
 */
final readonly class Job
{
    public const PRIORITY_HIGH = 100;
    public const PRIORITY_NORMAL = 50;
    public const PRIORITY_LOW = 10;

    /**
     * @param string               $type        Stable handler identifier (e.g. "mail.deliver", "seo.sitemap.rebuild").
     * @param array<string, mixed> $payload     JSON-serializable job arguments.
     * @param int                  $priority    Higher runs first (PRIORITY_HIGH > PRIORITY_NORMAL > PRIORITY_LOW).
     * @param int                  $delaySec    Delay in seconds before the job becomes eligible.
     * @param int                  $maxAttempts Bounded retry count before dead-lettering.
     */
    public function __construct(
        public string $type,
        public array $payload = [],
        public int $priority = self::PRIORITY_NORMAL,
        public int $delaySec = 0,
        public int $maxAttempts = 5,
    ) {
        if ($this->type === '') {
            throw new \InvalidArgumentException('Job type must not be empty.');
        }

        if ($this->delaySec < 0 || $this->maxAttempts < 1) {
            throw new \InvalidArgumentException('Job delaySec must be >= 0 and maxAttempts >= 1.');
        }
    }
}
