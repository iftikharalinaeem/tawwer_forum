/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/state/ReduxActions";

export default class DraftsPageActions extends ReduxActions {
    public static readonly GET_DRAFTS_REQUEST = "@@articleDrafts/GET_DRAFTS_REQUEST";
}
