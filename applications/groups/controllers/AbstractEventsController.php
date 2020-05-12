<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Groups\Models\EventPermissions;
use Vanilla\InjectableInterface;
use Vanilla\Models\GenericRecord;
use Vanilla\Navigation\BreadcrumbModel;

/**
 * New events controller.
 */
abstract class AbstractEventsController extends \Gdn_Controller implements InjectableInterface {

    const ALLOWED_PARENT_RECORD_TYPES = [ForumCategoryRecordType::TYPE, GroupRecordType::TYPE];

    /** @var EventModel */
    protected $eventModel;

    /** @var EventsApiController */
    protected $eventsApi;

    /** @var BreadcrumbModel */
    protected $breadcrumbModel;

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('global.js');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');

        parent::initialize();
    }

    /**
     * DI.
     * @inheritdoc
     */
    public function setDependencies(EventModel $eventModel, EventsApiController $eventsApi, BreadcrumbModel $breadcrumbModel) {
        $this->eventModel = $eventModel;
        $this->eventsApi = $eventsApi;
        $this->breadcrumbModel = $breadcrumbModel;
    }

    /**
     * Validate some parent record access and existance.
     *
     * @param string|null $parentRecordType
     * @param string|int|null $parentRecordID
     * @return array Tuple fo record type and recordID.
     *
     * @throws NotFoundException Not found or permissions error.
     * @throws ForbiddenException Permission error.
     */
    public function validateParentRecords(?string $parentRecordType, $parentRecordID): array {
        ///
        /// Validation and permission logic.
        ///
        $this->permission('Garden.SignIn.Allow');

        if ($parentRecordType === null) {
            throw new NotFoundException();
        }
        $parentRecordType ? strtolower($parentRecordType) : null;
        if (!in_array($parentRecordType, self::ALLOWED_PARENT_RECORD_TYPES)) {
            throw new NotFoundException();
        }

        if ($parentRecordID === null) {
            throw new NotFoundException();
        }

        // We have a slug on this id.
        $parentRecordID = GroupModel::idFromSlug($parentRecordID);

        // Validate our permissions for the records.
        $this->eventModel->checkParentEventPermission(EventPermissions::VIEW, $parentRecordType, $parentRecordID);

        return [$parentRecordType, $parentRecordID];
    }

    /**
     * Apply breadcrumbs to the controller for the event list.
     *
     * @param string $parentRecordType
     * @param int $parentRecordID
     */
    public function applyBreadcrumbs(string $parentRecordType, int $parentRecordID) {
        // Set breadcrumbs.
        $crumbs = $this->breadcrumbModel->getForRecord(new GenericRecord($parentRecordType, $parentRecordID));
        // Legacy page so pop off the first crumb.
        array_shift($crumbs);

        foreach ($crumbs as $crumb) {
            $this->addBreadcrumb($crumb->getName(), $crumb->getUrl());
        }
    }
}
