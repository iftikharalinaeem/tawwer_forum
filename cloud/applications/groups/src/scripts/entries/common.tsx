/*
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { registerDefaultNavItem } from "@vanilla/library/src/scripts/headers/navigationVariables";
import { t } from "@vanilla/i18n";

registerDefaultNavItem(() => {
    return {
        children: t("Groups Menu", t("Groups")),
        to: "/groups",
    };
});
