/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import isNumeric from "validator/lib/isNumeric";
import { logError } from "@vanilla/utils";

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard");
    userCards.forEach(userLink => {
        const { userid } = (userLink as HTMLSpanElement).dataset;
        if (userid && isNumeric(userid)) {
            const userID = parseInt(userid);
            const userName = (userLink as HTMLSpanElement).textContent;

            const cardHolder = document.createElement("span");
            cardHolder.classList.add("userCardHolder");
            ReactDOM.render(<a href={`#user-id${userID}`}>{userName}</a>, cardHolder);
            userLink.parentElement?.replaceChild(cardHolder, userLink);
        } else {
            logError(`Invalid user ID "${userid}" for userlink: `, userLink);
        }
    });
}
