<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Class ReverseProxySupportController
 */
class ReverseProxySupportController extends Gdn_Controller {

    /**
     * Add a validate endpoint.
     */
    public function validate() {
        $proxied = !empty($_SERVER['REVERSE_PROXY_SUPPORT_HTTP_HOST_ORIGINAL']);
        $forwardedIP = val('HTTP_X_FORWARDED_FOR', $_SERVER);
        $validationID = Gdn::request()->get('validationID', false);

        $isIDValid = $validationID && $validationID === c('ReverseProxySupport.ValidationID', null);

        $expectedProxyFor = Gdn::request()->get('expectedProxyFor', false);
        $wasProperlyProxied = true;
        if ($proxied && $expectedProxyFor !== false && rtrim($expectedProxyFor, '/') !== rtrim($_SERVER['HTTP_X_PROXY_FOR'], '/')) {
            $wasProperlyProxied = false;
        }

        $response = [
            'Valid' => $isIDValid && $proxied && $forwardedIP && $wasProperlyProxied,
        ];
        if ($response['Valid']) {
            $response += [
                'Host' => val('REVERSE_PROXY_SUPPORT_HTTP_HOST_ORIGINAL', $_SERVER),
                'X-Proxy-For' => $_SERVER['HTTP_X_PROXY_FOR'],
                'X-Forwarded-For' => $forwardedIP
            ];
        } else {
            $response['ErrorMessages'] = [];
            if (!$isIDValid) {
                $response['ErrorMessages'][] = t('ValidationID did not match. Are you sure your proxy point to the correct host?');
            }
            if (!$wasProperlyProxied) {
                $response['ErrorMessages'][] =  sprintf(
                    t("X-Proxy-For is not set properly:<br>Expected '%s'<br>Received '%s'"),
                    $expectedProxyFor,
                    $_SERVER['HTTP_X_PROXY_FOR']
                );
            }
            if (!$proxied) {
                $response['ErrorMessages'][] = t('The request was not passed through a proxy.');
            }
            if (!$forwardedIP) {
                $response['ErrorMessages'][] = t('Missing X-Forwarded-For header.');
            }
        }

        $response = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $callback = Gdn::request()->get('callback');
        if ($callback) {
            safeHeader('Content-Type: application/javascript');
            die("$callback($response);");
        } else {
            safeHeader('Content-Type: application/json');
            die($response);
        }
    }
}
