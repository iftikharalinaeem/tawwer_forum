/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { t } from "@vanilla/i18n";
import Translate from "@vanilla/library/src/scripts/content/Translate";
import { FrameBodyContainer } from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import React from "react";
import { useKBData } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";

interface IProps {
    kbID?: number;
}

export function KnowledgeBaseAddEditPermissions(props: IProps) {
    const { form } = useKBData();
    const { updateForm } = useKnowledgeBaseActions();
    return (
        <FrameBodyContainer>
            <DashboardFormGroup
                labelType={DashboardLabelType.WIDE}
                label={t("Enable Custom Permissions")}
                description={
                    <Translate
                        source="When enabled, this knowledge base will use custom permissions instead of the global defaults. <0>Read More</0>"
                        c0={text => (
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/185-per-knowledge-base-permissions">
                                {text}
                            </SmartLink>
                        )}
                    />
                }
            >
                <DashboardToggle
                    checked={form.hasCustomPermission}
                    onChange={hasCustomPermission => {
                        updateForm({ hasCustomPermission });
                    }}
                />
            </DashboardFormGroup>
            <DashboardFormList isBlurred={!form.hasCustomPermission}>
                <DashboardFormGroup
                    label={t("Viewers")}
                    description={t("Can read and search for content in this Knowledge Base.")}
                >
                    <div className="input-wrap">
                        <MultiRoleInput
                            label={""}
                            value={form.viewRoleIDs ?? []}
                            onChange={viewRoleIDs => {
                                updateForm({ viewRoleIDs });
                            }}
                        />
                    </div>
                </DashboardFormGroup>
                <DashboardFormGroup
                    label={t("Content Creators ")}
                    description={t("Can add articles, organize categories and curate content.")}
                >
                    <div className="input-wrap">
                        <MultiRoleInput
                            label={""}
                            value={form.editRoleIDs ?? []}
                            onChange={editRoleIDs => {
                                updateForm({ editRoleIDs });
                            }}
                        />
                    </div>
                </DashboardFormGroup>
            </DashboardFormList>
        </FrameBodyContainer>
    );
}
