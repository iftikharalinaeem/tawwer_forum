/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EditorRoute } from "@knowledge/routes/pageRoutes";
import Permission from "@library/features/users/Permission";
import { buttonClasses } from "@library/forms/buttonStyles";
import { SearchErrorIcon } from "@library/icons/common";
import Heading from "@library/layout/Heading";
import Paragraph from "@library/layout/Paragraph";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";
import React from "react";
import { pageErrorMessageClasses } from "@library/errorPages/pageErrorMessageStyles";
import {
    DefaultError,
    getErrorCode,
    IError,
    IErrorMessageProps,
    messageFromErrorCode,
    parseErrorCode,
} from "@library/errorPages/CoreErrorMessages";
import classNames from "classnames";
import { KbPermission } from "@knowledge/knowledge-bases/KbPermission";

export default function KbErrorMessages(props: IKbErrorMessageProps) {
    const classes = pageErrorMessageClasses();
    const error = {
        ...parseKbErrorCode(props, getErrorCode(props)),
        ...(props.error || {}),
    };
    const { message, messageAsParagraph, description } = error;
    return (
        <div className={classNames(classes.root, props.className)}>
            {error.icon}
            {!messageAsParagraph && <Heading depth={1} className={classes.title} title={message} />}
            {messageAsParagraph && <Paragraph className={classes.titleAsParagraph}>{message}</Paragraph>}
            {error.description && <Paragraph className={classes.description}>{description}</Paragraph>}
            {error.actionItem && <div className={classes.cta}>{error.actionItem}</div>}
        </div>
    );
}

function parseKbErrorCode(props: IKbErrorMessageProps, errorCode?: string | number): IError {
    const classes = pageErrorMessageClasses();
    const buttons = buttonClasses();
    const message = messageFromKbErrorCode(errorCode);

    switch (errorCode) {
        case DefaultKbError.NO_KNOWLEDGE_BASE: {
            return {
                message,
                description: (
                    <Permission permission="Garden.Settings.Manage">{t("Create one to get started!")}</Permission>
                ),
                actionItem: (
                    <Permission permission="Garden.Settings.Manage">
                        <LinkAsButton className={buttons.primary} to={"/knowledge-settings/knowledge-bases"}>
                            {t("New Knowledge Base")}
                        </LinkAsButton>
                    </Permission>
                ),
                icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
            };
        }
        case DefaultKbError.NO_ARTICLES: {
            const { knowledgeBaseID, knowledgeCategoryID } = props;
            return {
                message,
                description: knowledgeBaseID ? (
                    <KbPermission kbID={knowledgeBaseID} permission="articles.add">
                        {t("Create one to get started!")}
                    </KbPermission>
                ) : null,
                actionItem: knowledgeBaseID ? (
                    <KbPermission kbID={knowledgeBaseID} permission="articles.add">
                        <EditorRoute.Link className={buttons.primary} data={{ knowledgeBaseID, knowledgeCategoryID }}>
                            {t("New Article")}
                        </EditorRoute.Link>
                    </KbPermission>
                ) : null,
                icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
            };
        }
        case DefaultKbError.CATEGORY_NO_ARTICLES: {
            return {
                message,
                messageAsParagraph: true,
                icon: null,
            };
        }
        default: {
            const error = parseErrorCode(errorCode);
            error.message = messageFromKbErrorCode(errorCode);
            return error;
        }
    }
}

export function messageFromKbErrorCode(errorCode?: string | number) {
    switch (errorCode) {
        case 403:
        case DefaultError.PERMISSION:
            return t("No Permission");
        case DefaultKbError.NO_KNOWLEDGE_BASE:
            return t("There are no knowledge bases");
        case DefaultKbError.NO_ARTICLES:
            return t("No articles were found.");
        case DefaultKbError.CATEGORY_NO_ARTICLES:
            return t("This category does not have any articles.");
        case 404:
        case DefaultError.GENERIC:
        default:
            return messageFromErrorCode(errorCode);
    }
}

export interface IKbErrorMessageProps extends IErrorMessageProps {
    className?: string;
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
}

export enum DefaultKbError {
    GENERIC = "generic",
    PERMISSION = "permission",
    NOT_FOUND = "notfound",
    NO_KNOWLEDGE_BASE = "noknowledgebase",
    NO_ARTICLES = "noarticles",
    CATEGORY_NO_ARTICLES = "categorynoarticles",
}
