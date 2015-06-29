<?php

/**
 * Groups Application - Group Header Module
 *
 * Shows a small events list based on the provided Group or User context.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 * @since 1.0
 */

class GroupHeaderModule extends Gdn_Module {

  public $group;
  public $showOptions;
  public $showButtons;
  public $showMeta;
  public $showDescription;

  function __construct($group, $showOptions = true, $showButtons = true, $showMeta = false, $showDescription = false) {
    $this->group = $group;
    $this->showOptions = $showOptions;
    $this->showButtons = $showButtons;
    $this->showMeta = $showMeta;
    $this->showDescription = $showDescription;
  }

  public function assetTarget() {
    return 'Content';
  }

  /**
   * Render header
   *
   * @return type
   */
  public function toString() {
    include_once(PATH_APPLICATIONS .'/groups/views/group/group_functions.php');
    return $this->fetchView();
  }
}
