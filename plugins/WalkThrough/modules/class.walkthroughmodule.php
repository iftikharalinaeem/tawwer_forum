<?php if (!defined('APPLICATION')) { exit(); }

class WalkThroughModule extends Gdn_Module
{
    private $options = array(
        'redirectEnabled' => true
    );

    private $tourName;
    private $config;

    /**
     *
     * @param Gdn_Controller $Sender
     * @param Gdn_Router $ApplicationFolder
     */
    public function __construct($Sender = '', $ApplicationFolder = false) {
        parent::__construct($Sender, $ApplicationFolder);
        $this->_ApplicationFolder = 'plugins/WalkThrough';
        $this->fireEvent('Init');
    }

    public function setTour($name, $config) {
        $this->tourName = $name;
        $this->config = $config;
    }

    public function assetTarget() {
        return 'Foot';
    }

    public function toString() {
        $steps = $this->config;

        $CurrentUrl = rtrim(htmlEntityDecode(url()), '&');

        // adds the url property if needed
        foreach ($steps as $k => $dbStep) {
            $Page = val('page', $dbStep);
            if ($Page) {
                $steps[$k]['url'] = url($Page);
            }
        }

        $CurrentStepNumber = Gdn::session()->getCookie('-intro_currentstep', 0);

        // If possible and enabled, redirects to the page corresponding to the current step.
        if (isset($steps[$CurrentStepNumber]['url'])) {
            if ($this->options['redirectEnabled'] && $steps[$CurrentStepNumber]['url'] != $CurrentUrl) {
                redirectUrl($steps[$CurrentStepNumber]['url']);
            }
        }

        if (empty($steps)) {
            // Bail out if there are no steps to display!
            return '';
        }

        $this->setData('TourName', $this->tourName);
        $this->setData('IntroSteps', $steps);

        return parent::toString();
    }

}