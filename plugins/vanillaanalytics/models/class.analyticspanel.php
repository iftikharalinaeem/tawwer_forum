<?php
/**
 * AnalyticsPanel class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * A collection of analytics widgets.
 */
class AnalyticsPanel implements JsonSerializable {

    /**
     * @var bool Unique identifier, per dashboard, for this panel.
     */
    public $panelID;

    /**
     * @var array Collection of widgets in this panel.
     */
    protected $widgets = [];

    /**
     * AnalyticsPanel constructor.
     *
     * @param bool|integer|string $panelID Unique identifier for this panel.  False if none.
     */
    public function __construct($panelID = false) {
        if ($panelID) {
            $this->panelID = $panelID;
        }
    }

    /**
     * Add a new widget to this panel.
     *
     * @param array|string|AnalyticsWidget $widget Widget to add to the panel.
     * @return $this
     */
    public function addWidget($widget) {
        if ($widget instanceof Analyticswidget && $widget->isEnabled($widget->getType() === 'metric')) {
            // Is this an actual widget instance?
            $this->widgets[] = $widget;
        } elseif (is_array($widget)) {
            // Is this an array we need to iterate through?
            foreach ($widget as $currentWidget) {
                $this->addWidget($currentWidget);
            }
        } elseif (is_string($widget)) {
            // Is this a string we can use to lookup a dashboard?
            $widgetModel = new AnalyticsWidget();
            $newWidget = $widgetModel->getID($widget);
            if ($newWidget && $newWidget->isEnabled($newWidget->getType() === 'metric')) {
                $this->widgets[] = $newWidget;
            }
        }

        return $this;
    }

    /**
     * Fetch the list of widgets in this panel.
     *
     * @return array
     */
    public function getWidgets() {
        return $this->widgets;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize() {
        return [
            'panelID' => $this->panelID,
            'widgets' => $this->widgets
        ];
    }

    /**
     * Set the unique identifier for this panel.
     *
     * @param string $panelID Unique identifier, per dashboard, for this panel.
     * @return $this
     */
    public function setID($panelID) {
        $this->panelID = $panelID;
        return $this;
    }
}
