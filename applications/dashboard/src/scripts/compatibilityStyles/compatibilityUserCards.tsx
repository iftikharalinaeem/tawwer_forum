/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import isNumeric from "validator/lib/isNumeric";
import { logError } from "@vanilla/utils";
import { mountReact } from "@vanilla/react-utils/src";
import { UserCardModule } from "@library/features/users/modules/UserCardModule";
import InputTextBlock from "@library/forms/InputTextBlock";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export function applyCompatibilityUserCards(scope: HTMLElement | Document | undefined = document) {
    if (scope === undefined) {
        return;
    }
    const userCards = scope.querySelectorAll(".js-userCard");
    userCards.forEach(userLink => {
        const { userid } = (userLink as HTMLAnchorElement).dataset;
        if (userid && isNumeric(userid) && userLink instanceof HTMLAnchorElement) {
            const linkClasses = userLink.classList;
            linkClasses.remove("js-userCard"); // do not target more than once
            if (userLink.parentElement) {
                const placeholderElement = document.createElement("span");
                placeholderElement.classList.add("userCardWrapper");
                userLink.parentElement.replaceChild(placeholderElement, userLink); // I couldn't get mountReact to replace the link directly
                mountReact(
                    <UserCardModule userID={parseInt(userid)}>
                        <SmartLink
                            to={userLink.href}
                            title={userLink.title}
                            rel={userLink.rel}
                            target={userLink.target}
                            className={classNames(linkClasses.value)}
                        >
                            {userLink.innerText}
                        </SmartLink>
                    </UserCardModule>,
                    placeholderElement,
                    undefined,
                );
            }
        } else {
            logError(`Invalid user ID "${userid}" for userlink: `, userLink);
        }
    });

    cssOut(".userCardWrapper", {
        display: "inline-flex",
        zIndex: 1,
    });
}
