<?php

/**
 * Groups Application - Applicant List Module
 *
 */

class ApplicantListModule extends Gdn_Module {

    /**
     * @var array The applicants to render.
     */
    public $applicants;
    /**
     * @var array The group that the applicants are associated with.
     */
    public $group;
    /**
     * @var string The applicant section title.
     */
    public $title;
    /**
     * @var string The message to display if there are no applicants.
     */
    public $emptyMessage;
    /**
     * @var string The layout type, either 'modern' or 'table'.
     */
    public $layout;
    /**
     * @var bool Whether to add the 'approve', 'deny' and 'remove' buttons to applicant items.
     */
    public $withButtons;

    /**
     * Construct the ApplicantListModule object.
     *
     * @param array $applicants The applicants to render.
     * @param array $group The group that the applicants are associated with.
     * @param string $title The applicant section title.
     * @param string $emptyMessage The message to display if there are no applicants.
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withButtons Whether to add the 'approve', 'deny' and 'remove' buttons to applicant items.
     */
    public function __construct($applicants, $group, $title = '', $emptyMessage = '', $layout = '', $withButtons = true) {
        $this->applicants = $applicants;
        $this->group = $group;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->layout = $layout ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->_ApplicationFolder = 'groups';
    }

    /**
     * Compiles the data for the buttons for an applicant item.
     *
     * @param array $applicant The applicant item.
     * @param array $group The group that the applicant is associated with.
     * @return array The applicant buttons.
     */
    public function getApplicantButtons($applicant, $group) {
        $buttons = array();
        if (strtolower(val('Type', $applicant)) == 'application') {
            $approve['text'] = t('Approve');
            $approve['url'] = GroupUrl($group, 'approve')."?id={$applicant['GroupApplicantID']}";
            $approve['cssClass'] = 'Button SmallButton Hijack Button-Approve';

            $buttons[] = $approve;

            $deny['text'] = t('Deny');
            $deny['url'] = GroupUrl($group, 'approve')."?id={$applicant['GroupApplicantID']}&value=denied";
            $deny['cssClass'] = 'Button SmallButton Hijack Button-Deny';

            $buttons[] = $deny;
        } else if(strtolower(val('Type', $applicant)) == 'invitation') {
            $remove['text'] = t('Remove Invitation', 'Remove');
            $remove['url'] = GroupUrl($group, 'approve')."?id={$applicant['GroupApplicantID']}&value=denied";
            $remove['cssClass'] = 'Button SmallButton Hijack Button-Deny';

            $buttons[] = $remove;
        }
        return $buttons;
    }

    /**
     * Collect and organize the data for the applicant list.
     *
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param array $applicants The applicants to render.
     * @param array $group The group that the applicants are associated with.
     * @param string $title The applicant section title.
     * @param string $emptyMessage The message to display if there are no applicants.
     * @param bool $withButtons Whether to add the 'approve', 'deny' and 'remove' buttons to applicant items.
     * @return array An applicant list data array.
     */
    public function getApplicantsInfo($layout, $applicants, $group, $title, $emptyMessage, $withButtons) {

        $applicantList['layout'] = $layout;
        $applicantList['emptyMessage'] = $emptyMessage;
        $applicantList['title'] = $title;
        $applicantList['cssClass'] = 'ApplicantList';

        if ($layout == 'table') {
            $applicantList['columns'][0]['columnLabel'] = t('User');
            $applicantList['columns'][0]['columnCssClass'] = 'UserName';
            $applicantList['columns'][2]['columnLabel'] = '';
            $applicantList['columns'][2]['columnCssClass'] = 'Buttons';
        }

        foreach ($applicants as $applicant) {
            $applicantList['items'][] = $this->getApplicantInfo($applicant, $group, $layout, $withButtons);
        }

        return $applicantList;
    }

    /**
     * Collect and organize the data for an applicant item in the applicant list.
     *
     * @param array $applicant The applicant item.
     * @param array $group The group that the applicant is associated with.
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withButtons Whether to add the 'approve', 'deny' and 'remove' buttons to applicant items.
     * @return array A data array representing an applicant item in an applicant list.
     */
    public function getApplicantInfo($applicant, $group, $layout, $withButtons) {
        $item['heading'] = Gdn_Format::text(val('Name', $applicant));
        $item['url'] = userUrl($applicant);
        $item['imageSource'] = userPhotoUrl($applicant);
        $item['imageUrl'] = userUrl($applicant);
        $item['cssClass'] = val('Type', $applicant);

        if (class_exists('RankModel')) {
            $userModel = new UserModel();
            $applicantRankId = val('RankID', $userModel->getID(val('UserID', $applicant)));
            $rank = RankModel::Ranks($applicantRankId);
            $rankLabel = val('Label', $rank);
            $item['meta']['rank']['text'] = t('Rank') . ': ' . $rankLabel;
        }

        $type = (val('Type', $applicant) == 'Application') ? t('Applied on %s') : t('Invited on %s');
        $dateString = Gdn_Format::date(val('DateInserted', $applicant));
        $item['meta']['applyDate']['text'] = sprintf($type, $dateString);

        $item['text'] = htmlspecialchars($applicant['Reason']);
        $item['textCssClass'] = 'ApplicantReason';

        if ($layout == 'table') {
            $this->getApplicantTableItem($item, $applicant, $withButtons);
        } elseif ($withButtons) {
            $item['buttons'] = $this->getApplicantButtons($applicant, $group);
        }

        return $item;
    }


    /**
     * Adds the row data for an applicant item in a table layout applicant list.
     *
     * @param array $item The working applicant item for an applicant list.
     * @param array $applicant The applicant array we're parsing.
     * @param bool $withButtons Whether to add the 'approve', 'deny' and 'remove' buttons to applicant items.
     */
    public function getApplicantTableItem(&$item, $applicant, $withButtons) {
        $item['rows']['main']['type'] = 'main';
        $item['rows']['main']['cssClass'] = 'UserName';

        if ($withButtons) {
            $item['rows']['buttons']['type'] = 'buttons';
            $item['rows']['buttons']['buttons'] = $this->getApplicantButtons($applicant, $item);
            $item['rows']['buttons']['cssClass'] = 'pull-right';
        }
    }

    /**
     * Renders the applicant list.
     *
     * @return string HTML view
     */
    public function toString() {
        $this->applicants = $this->getApplicantsInfo($this->layout, $this->applicants, $this->group, $this->title, $this->emptyMessage, $this->withButtons);
        $controller = new Gdn_Controller();
        $controller->setData('list', $this->applicants);
        if (GroupPermission('Leader', $this->group)) {
            return $controller->fetchView('applicantlist', 'modules', 'groups');
        }
        return '';
    }
}
