<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Class ReverseProxySupportPlugin
 */
class ReverseProxySupportPlugin extends Gdn_Plugin {

    /**
     * Run on plugin activation.
     */
    public function setup() {
        // Let's make sure that we do not kill a site because we are activating this plugin with old configurations.
        saveToConfig('ReverseProxySupport.Redirect.Enabled', false);
    }

    /**
     * Create a reverseProxySupport endpoint on the settingsController.
     *
     * @param SettingsController $sender
     */
    public function settingsController_reverseProxySupport_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->addJsFile('jquery.popup.js');
        $sender->addCssFile('admin.css');
        $sender->addCssFile('magnific-popup.css', 'dashboard');
        $sender->addJsFile('reverseproxysupport.js', 'plugins/reverseproxysupport');
        $sender->title(sprintf(t('%s Settings'), t('Reverse Proxy Support')));
        $sender->Form = new Gdn_Form();

        $savedProxyURL = c('ReverseProxySupport.URL');

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'ReverseProxySupport.URL' => c('ReverseProxySupport.URL', ''),
            'ReverseProxySupport.Redirect.Enabled' => c('ReverseProxySupport.Redirect.Enabled'),
            'ReverseProxySupport.Redirect.ExcludedIPs' => c('ReverseProxySupport.Redirect.ExcludedIPs', ''),
        ]);
        $sender->Form->setModel($configurationModel);

        $redirectInputsVisibility = $sender->Form->getFormValue('ReverseProxySupport.Redirect.Enabled', c('ReverseProxySupport.Redirect.Enabled')) ? '' : ' foggy';
        $inputsDefinition = [
            'ReverseProxySupport.URL' => [
                'LabelCode' => 'Proxy URL',
                'Options' => ['id' => 'reverse-proxy-url'],
                'Description' => t(
                    "The URL must be comprised of:\n"
                    ."<br>- Scheme: Either 'http://'(force http) or 'https://'(force https) or '//'(won't force anything)\n"
                    ."<br>- Host: Hostname or ip of the proxy.\n"
                    ."<br>- Port (optional): Need to be specified if different than the defaults 80 and 443.\n"
                    ."<br>- Path (optional): Subdirectory from which the forum will be served.\n"
                ),
            ],
            'ReverseProxySupport.Redirect.Enabled' => [
                'Control' => 'toggle',
                'Options' => ['id' => 'js-foggy-redirect'],
                'LabelCode' => 'Redirect to proxy',
                'Description' => t('301 Redirect requests that are not issued to the reverse proxy'),
            ],
            'ReverseProxySupport.Redirect.ExcludedIPs' => [
                'Control' => 'textbox',
                'Options' => ['MultiLine' => true],
                'ItemWrap' => ["<li class=\"form-group js-foggy-redirect$redirectInputsVisibility\">\n", "\n</li>\n"],
                'LabelCode' => 'Excluded IPs',
                'Description' => t('List of IPs for which no redirection will be done. One IP address per line.'),
            ]
        ];
        $sender->setData('_FormInputDefinition', $inputsDefinition);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            // Validate reverse proxy URL
            $urlValid = false;
            $url = trim($sender->Form->getFormValue('ReverseProxySupport.URL', ''));
            if ($url === '') {
                $urlValid = true;
            } else if ($url) {
                $sanitizedURL = $this->filterProxyFor($url);
                if ($sanitizedURL) {
                    $urlValid = true;
                    $sender->Form->setFormValue('ReverseProxySupport.URL', $sanitizedURL);
                }
            } if (!$urlValid) {
                $sender->Form->addError('The specified URL does not meet the expected specifications.', 'ReverseProxySupport.URL');
            }

            // Force redirect to false since there is no proxy configured.
            if ($url === '') {
                $isRedirectOn = false;
            } else {
                // Coerce value to true/false
                $isRedirectOn = (bool)$sender->Form->getFormValue('ReverseProxySupport.Redirect.Enabled');
            }
            $sender->Form->setFormValue('ReverseProxySupport.Redirect.Enabled', $isRedirectOn);

            // Validate CSV IP list
            $ipList = $sender->Form->getFormValue('ReverseProxySupport.Redirect.ExcludedIPs');
            if ($ipList) {
                $ips = explode("\n", $ipList);
                $filteredIPs = [];
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV4])) {
                        $filteredIPs[] = $ip;
                    }
                }

                if ($filteredIPs) {
                    $sender->Form->setFormValue('ReverseProxySupport.Redirect.ExcludedIPs', implode("\n", $filteredIPs));
                } else {
                    $sender->Form->setFormValue('ReverseProxySupport.Redirect.ExcludedIPs', null);
                }
            }

            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $sender->render('configuration', '', 'plugins/reverseproxysupport');
    }

    /**
     * Hook as early as possible sine we are modifying the Request object.
     *
     * @param Garden\Container\Container $dic
     */
    public function container_init_handler($dic) {
        $xProxyFor = val('HTTP_X_PROXY_FOR', $_SERVER);
        $currentHTTPHost = $_SERVER['HTTP_HOST'];

        /** @var Gdn_Request $oldRequest */
        $oldRequest = $dic->get('Request');
        $path = $oldRequest->getPath();
        $isRequestValidation = preg_match('#/reverseproxysupport/validate/?#', $path) === 1;

        if ($isRequestValidation) {
            $filteredProxyFor = $this->filterProxyFor($xProxyFor);
            if ($filteredProxyFor) {
                $proxyHost = parse_url($filteredProxyFor, PHP_URL_HOST);
                $proxyPort = parse_url($filteredProxyFor, PHP_URL_PORT);
                $proxyPath = rtrim(parse_url($filteredProxyFor, PHP_URL_PATH), '/');
                $newHTTPHost = $proxyHost.($proxyPort ? ':'.$proxyPort : '');
            }
        } else {
            $proxyURL = c('ReverseProxySupport.URL');
            if (!$proxyURL) {
                return;
            }

            $isValidProxyFor = $xProxyFor && $xProxyFor === $proxyURL;

            $proxyHost = parse_url($proxyURL, PHP_URL_HOST);
            $proxyPort = parse_url($proxyURL, PHP_URL_PORT);
            $proxyPath = trim(parse_url($proxyURL, PHP_URL_PATH), '/');
            $newHTTPHost = $proxyHost.($proxyPort ? ':'.$proxyPort : '');

            if (!$isValidProxyFor) {
                // 301 redirect to the reverse proxy
                if (c('ReverseProxySupport.Redirect.Enabled') && $currentHTTPHost !== $newHTTPHost
                        && preg_match('#/settings/reverseproxysupport/?#', $path) !== 1
                        && !$this->isExcludedIP($oldRequest->ipAddress())) {
                    $query = $oldRequest->getQuery();
                    $pathAndQuery = $path.($query ? '?'.$query : '');
                    redirect($proxyURL.$pathAndQuery, 301);
                } else {
                    return;
                }
            }
        }

        if (!$newHTTPHost) {
            return;
        }

        $_SERVER['REVERSE_PROXY_SUPPORT_HTTP_HOST_ORIGINAL'] = $currentHTTPHost;
        $_SERVER['HTTP_HOST'] = $newHTTPHost;

        // Create the new request object with the modified environment variables.
        $request = Gdn_Request::create()->fromEnvironment();

        if ($proxyPath !== '') {
            $request->assetRoot($proxyPath);
            $request->webRoot($proxyPath);
        }

        // Assign the new request object to the container.
        $dic->setInstance('Request', $request);
    }

    /**
     * Filter a proxyFor url and returns it.
     *
     * @param $proxyFor
     * @return bool|string Return the filtered proxyFor on success and false on failure.
     */
    private function filterProxyFor($proxyFor) {
        $url = trim($proxyFor);
        if (!$proxyFor) {
            return false;
        }

        if (preg_match('#^(?:https?:)?//#', $url) !== 1) {
            return false;
        }

        $port = parse_url($url, PHP_URL_PORT);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $paddeScheme = !$scheme ? 'http'.(val('HTTPS', $_SERVER) ? 's' : '').':' : '';

        $sanitizedURL =
            ($scheme ? $scheme.'://' : '//')
            .parse_url($url, PHP_URL_HOST)
            .($port ? ':'.$port : '')
            .rtrim(parse_url($url, PHP_URL_PATH), '/')
        ;

        if (filter_var($paddeScheme.$sanitizedURL, FILTER_VALIDATE_URL, ['flags' => FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED])) {
            return $sanitizedURL;
        } else {
            return false;
        }
    }

    /**
     * Check is the supplied IP is excluded by the configuration.
     *
     * @param $ip
     * @return bool
     */
    private function isExcludedIP($ip) {
        $excludedIPlist = explode(",\n", c('ReverseProxySupport.Redirect.ExcludedIPs', ''));
        return in_array($ip, $excludedIPlist);
    }
}
