/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, t } from "@vanilla/library/src/scripts/utility/appUtils";
import { SearchFormContextProvider } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { TypePollsIcon } from "@vanilla/library/src/scripts/icons/searchIcons";

onReady(() => {
    SearchFormContextProvider.addSubType({
        label: t("Poll"),
        icon: <TypePollsIcon />,
        recordType: "poll",
        type: "poll",
    });
});
