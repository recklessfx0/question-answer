<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Validation;

/**
 * Validates untrusted boundary input against explicit schemas and sanitizes bidi control bytes.
 *
 * See docs/FRAMEWORK_ARCHITECTURE.md, docs/SECURITY_REQUIREMENTS.md (SEC-C-06, SEC-C-07).
 */
interface Validator
{
    /**
     * Validate $input against a map of field names to Rule lists.
     *
     * @param array<string, mixed>      $input
     * @param array<string, list<Rule>> $rules
     */
    public function validate(array $input, array $rules): ValidationResult;
}
