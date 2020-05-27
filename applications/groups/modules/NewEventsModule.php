<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Web\TwigRenderTrait;

/**
 * Module using the new events UI.
 */
class NewEventsModule extends Gdn_Module {

    use TwigRenderTrait;

    const MODE_UPCOMING = "upcoming";
    const MODE_ALL = "all";
    const MODE_PAST = "past";

    /** @var int */
    public $parentRecordID;

    /** @var string */
    public $parentRecordType;

    /** @var string */
    public $mode = self::MODE_UPCOMING;

    /** @var EventModel */
    private $eventModel;

    /** @var EventsApiController */
    private $eventApi;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->eventModel = \Gdn::getContainer()->get(EventModel::class);
        $this->eventApi = \Gdn::getContainer()->getArgs(EventsApiController::class);

        // Try to get the contextual category ID if it exists.
        $controller = \Gdn::controller();
        if (!$controller) {
            return;
        }

        $contextCategoryID = $controller->data('CategoryID', $controller->data(
            'ContextualCategoryID',
            $controller->data('Category.CategoryID', null)
        ));
        if ($contextCategoryID !== null) {
            $this->parentRecordType = ForumCategoryRecordType::TYPE;
            $this->parentRecordID = $contextCategoryID;
        } else {
            $this->parentRecordType = ForumCategoryRecordType::TYPE;
            $this->parentRecordID = -1;
        }
    }

    /**
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }


    /**
     * Rendering function.
     *
     * @return string
     */
    public function toString(): string {
        try {
            $events = $this->eventApi->index(array_merge($this->getDateFiltersForMode(), [
                'parentRecordType' => $this->parentRecordType,
                'parentRecordID' => $this->parentRecordID,
                'expand' => true,
            ]));

            if (count($events) === 0) {
                // Don't render if empty.
                return $this->renderTwig('@groups/EmptyNewEventsModule.twig', (array) $this);
            }

            $parentUrl = $this->eventModel->eventParentUrl($this->parentRecordType, $this->parentRecordID);

            $props = [
                'events' => $events,
                'viewMoreLink' => $parentUrl,
                'title'=> $this->getTitleForMode(),
            ];

            return $this->renderTwig('@groups/NewEventsModule.twig', [
                'props' => json_encode($props, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Garden\Web\Exception\HttpException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return "";
        }
    }

    /**
     * @return string
     */
    private function getTitleForMode(): string {
        switch ($this->mode) {
            case self::MODE_UPCOMING:
                return t('Upcoming Events');
            case self::MODE_PAST:
                return t('Past Events');
            case self::MODE_ALL:
            default:
                return t('Events');
        }
    }

    /**
     * Get date filters based on our mode.
     *
     * @return array
     */
    private function getDateFiltersForMode(): array {
        $dateNow = (new DateTime())->format(DateTime::RFC3339);
        switch ($this->mode) {
            case self::MODE_UPCOMING:
                return [
                    'dateEnds' => ">$dateNow",
                    'sort' => 'dateStarts',
                ];
            case self::MODE_PAST:
                return [
                    'dateStarts' => "<$dateNow",
                    'sort' => '-dateEnds',
                ];
            case self::MODE_ALL:
            default:
                return [
                    'sort' => '-dateStarts',
                ];
        }
    }
}
