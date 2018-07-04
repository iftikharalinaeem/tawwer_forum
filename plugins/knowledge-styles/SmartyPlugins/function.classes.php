<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Returns the classes for an element, combines defaults and dynamic classes.
 * @param defaultClasses string
 * @param dynamicClasses string
 * @return string
 */
function smarty_function_classes($params, &$smarty) {
    $default = val('default', $params, '');
    $extra = val('extra', $params, '');
	return trim($default.''.$extra);
}
