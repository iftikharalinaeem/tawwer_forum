<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Reference;
use ReverseProxy\Library\RequestRewriter;

$container = Gdn::getContainer();
$container
    ->rule(RequestRewriter::class)
    ->addCall("setProxyUrl", [new Reference(["Gdn_Configuration", "ReverseProxySupport.URL"])])
    ->addCall(
        "addIPExclusion",
        [new Reference(["Gdn_Configuration", "ReverseProxySupport.Redirect.ExcludedIPs"])]
    )
    ->setShared(true);

/** @var RequestRewriter $rewriter */
$rewriter = $container->get(RequestRewriter::class);

$path = $rewriter->getOriginalPath();
$proxyFor = $rewriter->getProxyFor();
$currentHTTPHost = $rewriter->getOriginalHost();

if (preg_match("#^reverseproxysupport/validate/?#", $path) === 1) {
    try {
        $filteredProxyFor = $rewriter->sanitizeUrl($proxyFor);
    } catch (InvalidArgumentException $e) {
        $filteredProxyFor = false;
    }
    $rewriter->setProxyUrl($filteredProxyFor);
}

if ($rewriter->getProxyUrl() && $rewriter->isProxyRequest()) {
    // Create the new request object with the modified environment variables.
    $request = $rewriter->rewriteRequest();

    // Assign the new request object to the container.
    $container->setInstance(Gdn_Request::class, $request);

    // Clear the cache of the Gdn object.
    Gdn::setContainer($container);
}
