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
    parent::__construct();
    $this->applicants = $applicants;
    $this->group = $group;
    $this->title = $title;
    $this->emptyMessage = $emptyMessage;
    $this->view = $view ?: c('Vanilla.Discussions.Layout');
    $this->_ApplicationFolder = 'groups';
  }

  public function getApplicantButtons($applicant, $group) {
    $buttons = array();
    if (strtolower(val('Type', $applicant)) == 'application') {
      $approve['text'] = t('Approve Applicant', 'Approve');
      $approve['url'] = GroupUrl($group, 'approve')."?id={$applicant['GroupApplicantID']}";
      $approve['cssClass'] = 'Button SmallButton Hijack Button-Approve';

      $buttons[] = $approve;

      $deny['text'] = t('Deny Applicant', 'Deny');
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
    $applicantList['moreLink'] = sprintf(T('All %s...'), $heading);
    $applicantList['moreUrl'] = Url(CombinePaths(array("/events/group/", GroupSlug($group))));
    $applicantList['moreCssClass'] = 'More';

    if ($view == 'table') {
      $applicantList['columns'][0]['columnLabel'] = t('User');
      $applicantList['columns'][0]['columnCssClass'] = 'UserName';
      $applicantList['columns'][1]['columnLabel'] = t('Reason');
      $applicantList['columns'][1]['columnCssClass'] = 'ApplicantReason';
    }

    foreach ($applicants as $applicant) {
      $applicantList['items'][] = $this->getApplicantInfo($applicant, $group, $view, true, $sectionId);
    }

    return $applicantList;
  }

  public function getApplicantInfo($applicant, $group, $view, $withButtons = true, $sectionId = false) {

    $item['heading'] = Gdn_Format::text(val('Name', $applicant));
    $item['url'] = userUrl($applicant);
    $item['imageSource'] = userPhotoUrl($applicant);
    $item['imageUrl'] = userUrl($applicant);
    $item['metaCssClass'] = '';

    if ($view != 'table') {
      $item['text'] = htmlspecialchars($applicant['Reason']);
      $item['textCssClass'] = 'ApplicantReason';
    }

    if ($withButtons) {
      $item['buttons'] = $this->getApplicantButtons($applicant, $group);
    }

    if ($view == 'table') {
      $this->getApplicantTableItem($item, $applicant);
    }

    return $item;
  }


  public function getApplicantTableItem(&$item, $applicant) {
    $item['rows']['main']['type'] = 'main';
    $item['rows']['main']['cssClass'] = 'UserName';

    $item['rows']['reason']['type'] = 'default';
    $item['rows']['reason']['text'] = htmlspecialchars($applicant['Reason']);
    $item['rows']['reason']['cssClass'] = 'ApplicantReason';
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
      return $controller->fetchView('eventlist', 'modules', 'groups');
    }
    return '';
  }
}
