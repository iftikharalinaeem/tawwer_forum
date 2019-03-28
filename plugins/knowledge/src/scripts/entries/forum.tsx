/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ConvertDiscussionModal } from "@knowledge/articleDiscussion/ConvertDiscussionModal";
import { UnlinkDiscussionModal } from "@knowledge/articleDiscussion/UnlinkDiscussionModal";
import { delegateEvent, mountModal } from "@library/dom/domUtils";
import React from "react";

delegateEvent("click", ".js-convertDiscussionToArticle", (event, triggeringElement) => {
    event.preventDefault();
    const discussionID = triggeringElement.getAttribute("data-discussionID") || null;
    if (discussionID === null) {
        return;
    }

    const id = parseInt(discussionID, 10);
    mountModal(<ConvertDiscussionModal discussionID={id} />);
});

delegateEvent("click", ".js-unlinkDiscussion", (event, triggeringElement) => {
    event.preventDefault();
    const discussionID = triggeringElement.getAttribute("data-discussionID") || null;
    if (discussionID === null) {
        return;
    }

    const id = parseInt(discussionID, 10);
    mountModal(<UnlinkDiscussionModal discussionID={id} />);
});
