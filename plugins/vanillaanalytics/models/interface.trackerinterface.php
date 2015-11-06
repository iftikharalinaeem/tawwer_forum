<?php
interface TrackerInterface {
    public function trackEvent($type, $details = array());
}
