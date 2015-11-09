<?php if (!defined('APPLICATION')) exit();

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
        $dbSteps = $this->config;

        // Inserts the steps number into the array
        foreach ($dbSteps as $k => $dbStep) {
            $dbSteps[$k]['vanilla_step'] = $k + 1;

            // @TODO : REMOVE debug
            $dbSteps[$k]['intro'] = 'Step# ' . $dbSteps[$k]['vanilla_step'] . '<br/>' . $dbStep['intro'];
        }

        $groups = array();
        $GroupIndex = 0;
        $CurrentUrl = rtrim(htmlEntityDecode(url()), '&');
        $LastStepUrl = $CurrentUrl;

        $StartUrl = Gdn::session()->getCookie('-intro_start_url');
        if (!$StartUrl) {
            Gdn::session()->setCookie('-intro_start_url', $CurrentUrl, 3600);
        } else {
            $LastStepUrl = $StartUrl;
        }

        $groups[0] = array(
            'show_on_url' => null,
            'steps' => array()
        );
        // split into logical group of steps
        foreach ($dbSteps as $dbStep) {
            $Page = val('page', $dbStep);
            if ($Page) {
                if (url($Page) == $LastStepUrl) {
                    $groups[$GroupIndex]['show_on_url'] = url($Page);
                } else {
                    $LastStepUrl = url($Page);

                    // new group needed
                    $GroupIndex++;
                    $groups[$GroupIndex] = array(
                        'show_on_url' => $LastStepUrl,
                        'steps' => array()
                    );
                }
            }
            $groups[$GroupIndex]['steps'][] = $dbStep;
        }

        $CurrentStepNumber = Gdn::session()->getCookie('-intro_currentstep', 1);


        $NextUrl = null;
        $IntroStartingStep = 1;


        // find the group that contains the current step
        foreach ($groups as $k => $group) {
            foreach ($group['steps'] as $step) {
                if ($step['vanilla_step'] == $CurrentStepNumber) {
                    break 2;
                }
            }
        }

        if ($group['show_on_url'] && $group['show_on_url'] != $CurrentUrl) {
            if ($this->options['redirectEnabled']) {
                redirectUrl($group['show_on_url']);
            }
            return '';
        }

        if (isset($groups[$k+1])) {
            $NextUrl = val('show_on_url', $groups[$k+1], null);
        }


        foreach ($group['steps'] as $k=> $step) {
            if ($step['vanilla_step'] == $CurrentStepNumber) {
                $IntroStartingStep = $k + 1;
                break;
            }
        }

        if (empty($group['steps'])) {
            // Bail out if there are no steps to display!
            return '';
        }


        $this->setData('TourName', $this->tourName);
        $this->setData('IntroSteps', $group['steps']);
        $this->setData('IntroStartingStep', $IntroStartingStep);
        $this->setData('NextUrl', $NextUrl);
        $this->setData('TotalSteps', count($dbSteps));

        return parent::toString();
    }

}