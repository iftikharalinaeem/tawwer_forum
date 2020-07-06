/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createElement } from "react";
import ReactDOM from "react-dom";

import StoryExampleMessagesDropDown from "@library/flyouts/StoryExampleDropDownMessages";
import isNumeric from "validator/lib/isNumeric";
import { logError } from "@vanilla/utils";
import { StoryContextProvider } from "@library/storybook/StoryContext";

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard");
    userCards.forEach(userLink => {
        const { userid } = (userLink as HTMLSpanElement).dataset;
        if (userid && isNumeric(userid)) {
            const userID = parseInt(userid);

            const cardHolder = document.createElement("span");
            cardHolder.classList.add("userCardHolder");
            ReactDOM.render(
                <span>hi</span>,
                // <StoryContextProvider>
                //     <StoryExampleMessagesDropDown />
                // </StoryContextProvider>,
                cardHolder,
            );
            userLink.parentElement?.replaceChild(cardHolder, userLink);
        } else {
            logError(`Invalid user ID "${userid}" for userlink: `, userLink);
        }
    });
}
