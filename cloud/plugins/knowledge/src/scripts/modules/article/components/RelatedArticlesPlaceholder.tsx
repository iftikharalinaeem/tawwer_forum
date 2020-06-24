/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unit } from "@library/styles/styleHelpers";
import { relatedArticlesClasses } from "@knowledge/modules/article/components/relatedArticlesStyles";
import SmartLink from "@library/routing/links/SmartLink";

/**
 * Implements the related articles component
 */
export function RelatedArticlesPlaceHolder() {
    const classes = relatedArticlesClasses();
    return (
        <>
            <LoadingRectange height={1} width={"100%"} className={classes.border} />
            <LoadingSpacer height={10} />
            <LoadingRectange height={12} width={"35%"} className={classes.header} />
            <LoadingSpacer height={5} />
            <ul className={classes.linkList}>
                <li className={classes.linkItem}>
                    <LoadingRectange height={6} width={"70%"} className={classes.link} />
                    <LoadingSpacer height={5} />
                </li>
                <li className={classes.linkItem}>
                    <LoadingRectange height={6} width={"70%"} className={classes.link} />
                    <LoadingSpacer height={5} />
                </li>
                <li className={classes.linkItem}>
                    <LoadingRectange height={6} width={"70%"} className={classes.link} />
                    <LoadingSpacer height={5} />
                </li>
                <li className={classes.linkItem}>
                    <LoadingRectange height={6} width={"70%"} className={classes.link} />
                    <LoadingSpacer height={5} />
                </li>
            </ul>
        </>
    );
}
