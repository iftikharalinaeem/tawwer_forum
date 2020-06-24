<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace ReverseProxy\Library;

use Gdn_Request;
use InvalidArgumentException;

/**
 * Simple utility class for aiding request rewriting.
 */
class RequestRewriter {

    /** @var string[] */
    private $ipExclusions = [];

    /** @var string */
    private $proxyUrl;

    /** @var Gdn_Request */
    private $request;

    /**
     * Initialize the rewriter.
     *
     * @param Gdn_Request $request
     */
    public function __construct(Gdn_Request $request) {
        $this->setRequest($request);
    }

    /**
     * Add an IP address that should not be rewritten. Multiple IPs can be specified, separated by a newline.
     *
     * @param string $ip
     */
    public function addIPExclusion($ip) {
        // We rely on the container to set this via config, so avoid the parameter type and check here.
        if (!is_string($ip)) {
            return;
        }

        $addIP = function ($newIP) {
            if (empty(trim($newIP)) || in_array($newIP, $this->ipExclusions)) {
                return;
            }

            $this->ipExclusions[] = $newIP;
        };

        $ips = explode("\n", $ip);
        foreach ($ips as $currentIP) {
            $addIP($currentIP);
        }
    }

    /**
     * Get the original request host.
     *
     * @return string|null
     */
    public function getOriginalHost(): ?string {
        return $this->request->getHost();
    }

    /**
     * Get the original requet IP address.
     *
     * @return string|null
     */
    public function getOriginalIPAddress(): ?string {
        return $this->request->getIP() ?? null;
    }

    /**
     * Get the original request path.
     *
     * @return string
     */
    public function getOriginalPath(): string {
        $path = $this->request->getPath() ?? "";
        $result = trim($path, "/");
        return $result;
    }

    /**
     * Get the original request path and query.
     *
     * @return string
     */
    public function getOriginalPathAndQuery(): string {
        return $this->request->pathAndQuery() ?? "";
    }

    /**
     * Get the configured X-Proxy-For header value.
     *
     * @return string|null
     */
    public function getProxyFor(): ?string {
        return $this->request->getHeader("X-Proxy-For") ?: null;
    }

    /**
     * Get the current proxy URL.
     *
     * @return string|null
     */
    public function getProxyUrl(): ?string {
        return $this->proxyUrl;
    }

    /**
     * Is the specified IP address excluded by our configuration?
     *
     * @param string $ip
     * @return bool
     */
    public function isExcludedIP(string $ip): bool {
        $result = in_array($ip, $this->ipExclusions);
        return $result;
    }

    /**
     * Is the original request a valid proxy request?
     *
     * @return bool
     */
    public function isProxyRequest(): bool {
        $result = $this->getProxyFor() && $this->getProxyFor() === $this->getProxyUrl();
        return $result;
    }

    /**
     * Parse a URL out to its usable parts.
     *
     * @param string $url
     * @return array
     */
    public function parseUrl(string $url): array {
        $host = parse_url($url, \PHP_URL_HOST) ?? null;
        $port = parse_url($url, \PHP_URL_PORT) ?? null;

        $result = [
            "host" => $host,
            "port" => $port,
            "path" => rtrim(parse_url($url, PHP_URL_PATH) ?? "", "/"),
            "hostAndPort" => $port ? "{$host}:{$port}" : $host,
        ];
        return $result;
    }

    /**
     * Create a new request, based on the original, but rewritten based on the current configuration.
     *
     * @return Gdn_Request
     */
    public function rewriteRequest(): Gdn_Request {
        if ($this->proxyUrl) {
            $result = new RewrittenRequest();
            $result->fromImport($this->request);

            ["path" => $proxyPath, "hostAndPort" => $newHost] = $this->parseUrl($this->proxyUrl);

            $result->host($newHost);
            if (!empty($proxyPath)) {
                $result->setAssetRoot($proxyPath);
                $result->setRoot($proxyPath);
            }
        } else {
            $result = clone $this->request;
        }

        return $result;
    }

    /**
     * Given a URL, return a sanitized version.
     *
     * @param string $url
     * @return string
     * @throws InvalidArgumentException If URL is not invalid.
     */
    public function sanitizeUrl(string $url): string {
        $url = trim($url);
        if (!$url) {
            throw new InvalidArgumentException("URL must not be an empty string.");
        }

        if (preg_match('#^(?:https?:)?//#', $url) !== 1) {
            throw new InvalidArgumentException("URL must specify absolute or relative protocol.");
        }

        $port = parse_url($url, PHP_URL_PORT);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $paddedScheme = !$scheme ? 'http'.(val('HTTPS', $_SERVER) ? 's' : '').':' : '';

        $sanitizedURL =
            ($scheme ? $scheme.'://' : '//')
            .parse_url($url, PHP_URL_HOST)
            .($port ? ':'.$port : '')
            .rtrim(parse_url($url, PHP_URL_PATH), '/')
        ;

        $filterResult = filter_var(
            $paddedScheme.$sanitizedURL,
            FILTER_VALIDATE_URL,
            ['flags' => FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED]
        );
        if ($filterResult) {
            return $sanitizedURL;
        } else {
            throw new InvalidArgumentException("URL is not valid.");
        }
    }

    /**
     * @param string $url
     */
    public function setProxyUrl($url) {
        // We rely on the container to set this via config, so avoid the parameter type and check here.
        if (!is_string($url)) {
            $this->proxyUrl = null;
            return;
        }
        $this->proxyUrl = $url;
    }

    /**
     * Set the request.
     *
     * @param Gdn_Request $request
     */
    public function setRequest(Gdn_Request $request) {
        $this->request = $request;
    }
}
