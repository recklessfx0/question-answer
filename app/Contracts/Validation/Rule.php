<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Validation;

/**
 * Single reusable input validation rule returning a message ID on failure.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/I18N.md.
 */
interface Rule
{
    /**
     * Validate a single field value.
     *
     * @return string|null Validation message ID (from the "validation" domain) on failure, or null when valid.
     */
    public function validate(string $field, mixed $value): ?string;
}
