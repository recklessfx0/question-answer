<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Validation;

/**
 * Immutable result of input validation containing typed clean data or localized error keys.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md and docs/I18N.md.
 */
final readonly class ValidationResult
{
    /**
     * @param array<string, mixed>        $validated Clean, validated field values.
     * @param array<string, list<string>> $errors    Field name to list of validation message IDs.
     */
    public function __construct(
        public array $validated,
        public array $errors = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
