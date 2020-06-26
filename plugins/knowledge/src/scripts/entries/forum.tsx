/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ConvertDiscussionModal } from "@knowledge/articleDiscussion/ConvertDiscussionModal";
import { UnlinkDiscussionModal } from "@knowledge/articleDiscussion/UnlinkDiscussionModal";
import { delegateEvent } from "@vanilla/dom-utils";
import { mountModal } from "@library/modal/Modal";
import React from "react";
import { registerDefaultNavItem } from "@library/headers/navigationVariables";
import { getMeta, t } from "@library/utility/appUtils";
import { registerKbSearchDomain } from "@knowledge/search/registerKbSearchDomain";

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

const kbEnabled = getMeta("siteSection.apps.knowledgeBase", true);
const forumEnabled = getMeta("siteSection.apps.forum", true);

if (kbEnabled && forumEnabled) {
    registerDefaultNavItem(() => {
        return {
            children: t("Help Menu", "Help"),
            permission: "kb.view",
            to: "/kb",
        };
    });
}

registerKbSearchDomain();
