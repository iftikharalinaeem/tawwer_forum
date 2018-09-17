<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Garden;

interface ControllerActionAutodetectInterface {
    public static function detectAction(\Gdn_Request $request, array $pathArgs) : string;
}
