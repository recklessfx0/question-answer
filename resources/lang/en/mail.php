<?php

declare(strict_types=1);

/**
 * Transactional email message catalogue (English, ICU MessageFormat).
 * Rendered in the recipient's stored locale. See docs/MAIL.md and docs/I18N.md.
 */
return [
    'mail.verify_email.subject' => 'Verify your email address',
    'mail.password_reset.subject' => 'Reset your password',
    'mail.email_change.subject' => 'Confirm your new email address',
    'mail.security_alert.subject' => 'Security alert for your account',
    'mail.security_alert.recovery_used' => 'A single-use recovery code was used to sign in to your account.',
    'mail.security_alert.passkey_clone' => 'A passkey sign-in was blocked due to a suspicious signature counter.',
];
