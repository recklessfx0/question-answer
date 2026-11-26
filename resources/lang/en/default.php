<?php

declare(strict_types=1);

/**
 * Default UI message catalogue (English, ICU MessageFormat).
 * Values are data only — never HTML. See docs/I18N.md and ADR 0001.
 */
return [
    'app.name' => 'QuestionAnswer',
    'nav.questions' => 'Questions',
    'nav.topics' => 'Topics',
    'nav.search' => 'Search',
    'question.answers.count' => '{count, plural, =0 {No answers yet} one {# answer} other {# answers}}',
    'question.answer.accepted' => 'Answer accepted by {editor}.',
    'error.not_found' => 'The requested resource could not be found.',
    'error.forbidden' => 'You do not have permission to perform this action.',
];
