<?php

declare(strict_types=1);

namespace QuestionAnswer\Contracts\Http;

/**
 * Sole sanctioned outbound HTTP client for egress (AI providers, IndexNow, webhooks, imports).
 *
 * Implementations enforce SEC-C-05 at every request and redirect hop:
 * - Allow-listed schemes (https by default; http only when explicitly permitted)
 * - DNS resolution and rejection of loopback, RFC 1918 private, link-local,
 *   CGNAT and cloud metadata IPv4/IPv6 ranges at dial time (anti-DNS-rebinding)
 * - Strict connect/read timeouts, bounded redirects and response byte caps
 * - TLS certificate verification enabled by default
 *
 * See docs/SECURITY_REQUIREMENTS.md (SEC-C-05), docs/THREAT_MODEL.md (B6) and ADR 0004 (D7).
 */
interface OutboundHttpClient
{
    /**
     * Perform an SSRF-validated outbound HTTP request.
     *
     * @param string                $method       HTTP verb (GET, POST, etc.).
     * @param string                $url          Target URL.
     * @param array<string, string> $headers      Request headers.
     * @param string                $body         Optional request body.
     * @param int                   $timeoutSec   Total timeout cap in seconds.
     * @param int                   $maxBodyBytes Maximum accepted response body bytes.
     *
     * @throws \InvalidArgumentException When the URL scheme, host or parameters are disallowed.
     * @throws \RuntimeException         When SSRF IP validation, TLS, timeout or size limits fail.
     */
    public function send(
        string $method,
        string $url,
        array $headers = [],
        string $body = '',
        int $timeoutSec = 10,
        int $maxBodyBytes = 1048576,
    ): Response;
}
