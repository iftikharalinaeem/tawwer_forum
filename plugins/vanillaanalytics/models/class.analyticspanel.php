<?php

/**
 * A collection of analytics widgets.
 */
class AnalyticsPanel {

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
     * @param bool $panelID
     */
    public function __construct($panelID = false) {
        if ($panelID) {
            $this->panelID = $panelID;
        }
    }

    /**
     * Add a new widget to this panel.
     *
     * @param AnalyticsWidget $widget Widget to add to the panel.
     * @return $this
     */
    public function addWidget(AnalyticsWidget $widget) {
        $this->widgets[] = $widget;
        return $this;
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
