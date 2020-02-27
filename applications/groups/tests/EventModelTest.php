<?php
/**
 *
 */

namespace VanillaTests\Dashboard\Models;

use PHPUnit\Framework\TestCase;
use EventModel;

/**
 * Tests for the event model.
 */
class EventModelTest extends TestCase {

    /**
     * An invalid $dateString date should return array with false values.
     */
    public function testInvalidDateString() {
        $model = new EventModel();
        $expected = [false, false, false, false];
        $actual = $model::formatEventDate('dnsfids');
        $this->AssertSame($expected, $actual);
    }
}
