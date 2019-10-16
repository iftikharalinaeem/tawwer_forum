/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { t } from "@vanilla/i18n";

interface IProps {
    forStatus: KnowledgeBaseStatus;
}

export function EmptyKnowledgeBasesResults(props: IProps) {
    return (
        <div className="padded">
            <p>
                {props.forStatus === KnowledgeBaseStatus.DELETED
                    ? t('No "deleted" knowledge bases found.')
                    : t("Add a knowledge base to get started.")}
            </p>
        </div>
    );
}
