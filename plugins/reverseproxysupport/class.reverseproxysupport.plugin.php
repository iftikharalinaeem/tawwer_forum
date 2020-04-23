<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use ReverseProxy\Library\RequestRewriter;
use ReverseProxy\Library\RewrittenRequest;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class ReverseProxySupportPlugin
 */
class ReverseProxySupportPlugin extends Gdn_Plugin {

    /** @var ConfigurationInterface */
    private $config;

    /** @var bool */
    private $forceRedirect = false;

    /** @var bool */
    private $isDebug = false;

    /** @var string */
    private $proxyRoot;

    /** @var RequestRewriter */
    private $rewriter;

    /**
     * Setup the addon.
     *
     * @param ConfigurationInterface $config
     * @param Gdn_Dispatcher $dispatcher
     * @param RequestRewriter $rewriter
     */
    public function __construct(ConfigurationInterface $config, RequestRewriter $rewriter) {
        $this->config = $config;
        $this->forceRedirect = (bool)$config->get(
            "ReverseProxySupport.Redirect.Enabled",
            false
        );
        $this->isDebug = debug();
        $this->rewriter = $rewriter;

        if ($proxyUrl = $rewriter->getProxyUrl()) {
            ["path" => $proxyPath] = $rewriter->parseUrl($proxyUrl);
            $this->proxyRoot = $proxyPath;
        }
    }

    /**
     * Run on plugin activation.
     */
    public function setup() {
        // Let's make sure that we do not kill a site because we are activating this plugin with old configurations.
        $this->config->saveToConfig('ReverseProxySupport.Redirect.Enabled', false);
        $this->config->saveToConfig('ReverseProxySupport.ValidationID', uniqid());
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

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'ReverseProxySupport.URL' => c('ReverseProxySupport.URL', ''),
            'ReverseProxySupport.Redirect.Enabled' => c('ReverseProxySupport.Redirect.Enabled'),
            'ReverseProxySupport.Redirect.ExcludedIPs' => c('ReverseProxySupport.Redirect.ExcludedIPs', ''),
        ]);
        $sender->Form->setModel($configurationModel);

        $redirectInputsVisibility = $sender->Form->getFormValue(
            'ReverseProxySupport.Redirect.Enabled',
            c('ReverseProxySupport.Redirect.Enabled')
        ) ? '' : ' foggy';
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
            } elseif ($url) {
                try {
                    $sanitizedURL = $this->rewriter->sanitizeUrl($url);
                } catch (InvalidArgumentException $e) {
                    $sanitizedURL = false;
                }
                if ($sanitizedURL) {
                    $urlValid = true;
                    $sender->Form->setFormValue('ReverseProxySupport.URL', $sanitizedURL);
                }
            } if (!$urlValid) {
                $sender->Form->addError(
                    'The specified URL does not meet the expected specifications.',
                    'ReverseProxySupport.URL'
                );
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
                    if (filter_var($ip, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6])) {
                        $filteredIPs[] = $ip;
                    }
                }

                if ($filteredIPs) {
                    $sender->Form->setFormValue(
                        'ReverseProxySupport.Redirect.ExcludedIPs',
                        implode("\n", $filteredIPs)
                    );
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
     * Prevent the reverseproxysupport setting page from being blocked/redirected.
     *
     * @param Gdn_Dispatcher $sender
     * @param array $args
     */
    public function base_beforeBlockDetect_handler($sender, $args) {
        $args['BlockExceptions']['#^settings/reverseproxysupport/?#'] = Gdn_Dispatcher::BLOCK_NEVER;
    }

    /**
     * Hook in early to handle redirects.
     *
     * @param Gdn_Dispatcher $dispatcher
     */
    public function gdn_dispatcher_appStartup(Gdn_Dispatcher $dispatcher) {
        if (!$this->forceRedirect || $this->rewriter->isProxyRequest() === true) {
            return;
        }

        $currentHTTPHost = $this->rewriter->getOriginalHost();
        $path = $this->rewriter->getOriginalPath();
        $proxyURL = $this->rewriter->getProxyUrl();
        ["hostAndPort" => $proxyHostAndPort] = $this->rewriter->parseUrl($proxyURL);

        $isExcludedIP = $this->rewriter->isExcludedIP($this->rewriter->getOriginalIPAddress());
        $skipRedirect = ($currentHTTPHost === $proxyHostAndPort) || $isExcludedIP;

        if ($skipRedirect) {
            return;
        }

        // Do not redirect block exceptions.
        $blockExceptions = $dispatcher->getBlockExceptions();
        foreach ($blockExceptions as $blockException => $blockLevel) {
            if ($blockLevel <= Gdn_Dispatcher::BLOCK_PERMISSION && preg_match($blockException, $path)) {
                return;
            }
        }

        $pathAndQuery = $this->rewriter->getOriginalPathAndQuery();
        $responseCode = $this->isDebug ? 302 : 301;
        redirectTo("{$proxyURL}/{$pathAndQuery}", $responseCode, false);
    }

    /**
     * Enforce asset and web root if the request was rewritten.
     */
    public function gdn_dispatcher_beforeControllerMethod_handler() {
        $request = Gdn::request();

        if (!$this->proxyRoot || !($request instanceof RewrittenRequest)) {
            return;
        }

        if ($request->getAssetRoot() !== $this->proxyRoot) {
            $request->setAssetRoot($this->proxyRoot);
            $request->webRoot($this->proxyRoot);
        }
    }
}
