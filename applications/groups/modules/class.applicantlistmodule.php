<?php

/**
 * Groups Application - Group List Module
 *
 */

class ApplicantListModule extends Gdn_Module {

    public $applicants;
    public $group;
    public $title;
    public $emptyMessage;
    public $view;

    public function __construct($applicants, $group, $title = '', $emptyMessage = '', $view = '') {
        $this->applicants = $applicants;
        $this->group = $group;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->view = $view ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->_ApplicationFolder = 'groups';
    }

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

    public function getApplicantsInfo($view, $applicants, $group, $heading, $emptyMessage = '', $sectionId = '') {

        $applicantList['view'] = $view;
        $applicantList['emptyMessage'] = $emptyMessage;
        $applicantList['title'] = $heading;
        $applicantList['cssClass'] = 'ApplicantList';

        if ($view == 'table') {
            $applicantList['columns'][0]['columnLabel'] = t('User');
            $applicantList['columns'][0]['columnCssClass'] = 'UserName';
            $applicantList['columns'][2]['columnLabel'] = '';
            $applicantList['columns'][2]['columnCssClass'] = 'Buttons';
        }

        foreach ($applicants as $applicant) {
            $applicantList['items'][] = $this->getApplicantInfo($applicant, $group, $view, true, $sectionId);
        }

        return $applicantList;
    }

    public function getApplicantInfo($applicant, $group, $view) {

        if ($view != 'table') {
            $item['buttons'] = $this->getApplicantButtons($applicant, $group);
        }

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

        if ($view == 'table') {
            $this->getApplicantTableItem($item, $applicant);
        }

        return $item;
    }


    public function getApplicantTableItem(&$item, $applicant) {
        $item['rows']['main']['type'] = 'main';
        $item['rows']['main']['cssClass'] = 'UserName';

        $item['rows']['buttons']['type'] = 'buttons';
        $item['rows']['buttons']['buttons'] = $this->getApplicantButtons($applicant, $item);
        $item['rows']['buttons']['cssClass'] = 'pull-right';
    }

    /**
     * Render groups
     *
     * @return type
     */
    public function toString() {
        $this->applicants = $this->getApplicantsInfo($this->view, $this->applicants, $this->group, $this->title, $this->emptyMessage);
        $controller = new Gdn_Controller();
        $controller->setData('list', $this->applicants);
        if (GroupPermission('Leader', $this->group)) {
            return $controller->fetchView('applicantlist', 'modules', 'groups');
        }
        return '';
    }
}
