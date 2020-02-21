/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { useKBData } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { t } from "@vanilla/i18n";
import Translate from "@vanilla/library/src/scripts/content/Translate";
import { FrameBodyContainer } from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import React from "react";

interface IProps {
    kbID?: number;
}

export function KnowledgeBaseAddEditUniversal(props: IProps) {
    const { form } = useKBData();
    const { updateForm } = useKnowledgeBaseActions();
    return (
        <FrameBodyContainer>
            <DashboardFormGroup
                labelType={DashboardLabelType.WIDE}
                label={t("Enable Universal Content ")}
                description={
                    <Translate
                        source={
                            "Content in this KB is Universal and will be available and searchable in multiple KBs. <0>Read more</0>"
                        }
                        c0={text => (
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/186-universal-knowledge-base-content">
                                {text}
                            </SmartLink>
                        )}
                    />
                }
            >
                <DashboardToggle
                    checked={form.isUniversalSource}
                    onChange={isUniversalSource => {
                        updateForm({ isUniversalSource });
                    }}
                />
            </DashboardFormGroup>
            <DashboardFormList isBlurred={!form.isUniversalSource}>
                <div style={{ paddingTop: 24 }}>Hello Universal Content (Will be replaced in implementation PR)</div>
            </DashboardFormList>
        </FrameBodyContainer>
    );
}
