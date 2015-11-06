<?php if (!defined('APPLICATION')) exit();

class WalkThroughModule extends Gdn_Module
{
    private $options = array(
        'redirectEnabled' => true
    );

    /**
     *
     * @param Gdn_Controller $Sender
     * @param Gdn_Router $ApplicationFolder
     */
    public function __construct($Sender = '', $ApplicationFolder = false) {
        parent::__construct($Sender, $ApplicationFolder);
        $this->_ApplicationFolder = 'plugins/WalkThrough';
    }

    public function assetTarget() {
        return 'Foot';
    }

    private function getStepsWithRigidPages() {
        return array(
            array(
                'intro' => 'Welcome to this forum.  We will guide you though the features',
                'page' => 'discussions'
            ),
            array(
                'element' => '.SiteMenu',
                'intro' => 'This is the site menu',
            ),
            array(
                'element' => '.ActivityFormWrap',
                'intro' => 'You can add a new activity post in here',
                'page' => 'activity',
            ),
            array(
                'element' => '#Panel',
                'intro' => 'This is the panel section',
                'page' => 'discussions'
            ),

        );
    }

    private function getStepsWithNoPages() {
        return array(
            array(
                'intro' => 'Welcome to this forum.  We will guide you though the features',
//                'vanilla_to'
//                'position' => 'bottom',
            ),
            array(
                'element' => '.SiteMenu',
                'intro' => 'This is the site menu',
//                'position' => 'bottom',
            ),
            array(
                'element' => '#Panel',
                'intro' => 'This is the panel section',
//                'position' => 'bottom'
            ),

        );
    }

    public function toString() {
        $dbSteps = $this->getStepsWithRigidPages();
//        $dbSteps = $this->getStepsWithNoPages();

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


        $this->setData('IntroSteps', $group['steps']);
        $this->setData('IntroStartingStep', $IntroStartingStep);
        $this->setData('NextUrl', $NextUrl);
        $this->setData('TotalSteps', count($dbSteps));

        return parent::toString();
    }

}