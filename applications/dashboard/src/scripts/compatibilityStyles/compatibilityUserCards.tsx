/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";

import StoryExampleMessagesDropDown from "@library/flyouts/StoryExampleDropDownMessages";

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard:not(.js-initialized)");
    userCards.forEach(userLink => {
        userLink.classList.add("js-initialized");
        console.log("userLink: ", userLink);
        // Temporarily using example dropdown until i get the real component.
        ReactDOM.render(<StoryExampleMessagesDropDown />, userLink);
    });
}
