<?php
/**
 * AnalyticsWidget class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanillaanalytics
 */

/**
 * A data container for graphical representation in the browser (e.g. charts, metrics).
 */
class AnalyticsWidget implements JsonSerializable {

    /**
     * @var bool Is this widget bookmarked by the current user?
     */
    protected $bookmarked = null;

    /**
     * @var string Name of a function in the handler's JavaScript class to call against query results.
     */
    protected $callback = null;

    /**
     * @var array A collection of data to drive the widget.
     */
    protected $data = [];

    /**
     * @var array A list of default data widgets.
     */
    static protected $defaults = [];

    /**
     * @var string Name of the JavaScript object to handle the data.
     */
    protected $handler;

    /** @var Gdn_SQLDriver Contains the sql driver for the object. */
    public $sql;

    /**
     * @var array A collection of special event properties, used to indicate support (e.g. cat1, roleType)
     */
    protected $supports = [];

    /**
     * @var string Title of this widget.
     */
    protected $title = '';

    /**
     * @var string Type of this widget: chart or metric.
     */
    protected $type;

    /**
     * @var string Unique identifier for this widget.
     */
    public $widgetID;

    /**
     * AnalyticsWidget constructor.
     * @param bool $widgetID
     * @param array $data
     * @param bool $handler
     */
    public function __construct($widgetID = false, $data = [], $handler = false) {
        $this->sql = Gdn::database()->sql();

        if ($widgetID) {
            $this->setID($widgetID);
        }
    }

    /**
     * Add an event property to this widget's support array.
     *
     * @param array|string $eventProperty
     * @return $this
     */
    public function addSupport($eventProperty) {
        if (is_array($eventProperty)) {
            foreach ($eventProperty as $property) {
                $this->addSupport($property);
            }
        }

        $this->supports[] = $eventProperty;
        return $this;
    }

    /**
     * Retrieve the name of the handler's JavaScript class to call on query results.
     *
     * @return string
     */
    public function getCallback() {
        return $this->callback;
    }

    /**
     * Retrieve the data for this widget.
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Retrieve a list of default widgets.
     *
     * @return array
     */
    public function getDefaults() {
        if (empty(static::$defaults)) {
            static::$defaults = AnalyticsTracker::getInstance()->getDefaultWidgets();
        }

        return static::$defaults;
    }

    /**
     * Grab the name of the handler for this widget.
     *
     * @return string
     */
    public function getHandler() {
        return $this->handler;
    }

    /**
     * Retrieve an existing widget configuration by its unique identifier.
     *
     * @param string $widgetID
     * @return bool|AnalyticsWidget An instance of AnalyticsWidget on success, false on failure.
     */
    public function getID($widgetID) {
        $defaults = $this->getDefaults();
        $result = false;

        if (array_key_exists($widgetID, $defaults)) {
            $result = $defaults[$widgetID];
        }

        return $result;
    }

    /**
     * Grab the support slugs for this widget.
     *
     * @return array
     */
    public function getSupports() {
        return $this->supports;
    }

    /**
     * Fetch the title of this widget.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Fetch this widget's type.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Is this widget bookmarked by the current user?
     *
     * @return bool
     */
    public function isBookmarked() {
        if ($this->bookmarked === null) {
            $dashboardModel = new AnalyticsDashboard();
            $this->bookmarked = in_array(
                $this->widgetID,
                $dashboardModel->getUserDashboardWidgets(AnalyticsDashboard::DASHBOARD_PERSONAL)
            );
        }

        return $this->bookmarked;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize() {
        return [
            'bookmarked'      => $this->isBookmarked(),
            'callback'        => $this->getCallback(),
            'data'            => $this->data,
            'handler'         => $this->handler,
            'supports'        => $this->supports,
            'title'           => $this->title,
            'type'            => $this->type,
            'widgetID'        => $this->widgetID
        ];
    }

    /**
     * Set a callback for query results.  This callback must be a member of the handler's JavaScript class.
     *
     * @param string $callback
     * @return $this
     */
    public function setCallback($callback) {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Flag whether or not this widget supports the category selector.
     *
     * @param bool $categorySupport Is the category selector supported by this widget?
     * @return $this
     */
    public function setCategorySupport($categorySupport) {
        $this->categorySupport = (bool)$categorySupport;
        return $this;
    }

    /**
     * Set this widget's data.
     *
     * @param array $data A collection of data to be passed to the JavaScript handler.
     * @return $this
     */
    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Assign the JavaScript handler that will be used for this widget.
     *
     * @param string $handler Name of the JavaScript object to render this widget.
     * @return $this
     */
    public function setHandler($handler) {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Set the unique identifier for this widget.
     *
     * @param string $widgetID This widget's unique identifier.
     * @return $this
     */
    public function setID($widgetID) {
        $this->widgetID = $widgetID;
        return $this;
    }

    /**
     * Set the title for this widget.
     *
     * @param string $title New title for this widget.
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the type of this widget.
     *
     * @param string $type A widget type: chart or metric.
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
}
