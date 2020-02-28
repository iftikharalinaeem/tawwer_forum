/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import {
    useKBData,
    useKnowledgeBases,
    KnowledgeBaseStatus,
    useUniversalSources,
    useAllowedUniversalTargets,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { t } from "@vanilla/i18n";
import Translate from "@vanilla/library/src/scripts/content/Translate";
import { FrameBodyContainer } from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import React, { useState } from "react";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import CheckBox from "@vanilla/library/src/scripts/forms/Checkbox";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import isEqual from "lodash/isEqual";
import SearchBar from "@vanilla/library/src/scripts/features/search/SearchBar";
import { inputClasses } from "@vanilla/library/src/scripts/forms/inputStyles";
import classNames from "classnames";
import { knowledgeBaseAddEditClasses } from "@knowledge/knowledge-settings/addEdit/knowledgeBaseAddEditStyles";
import { normalizeString } from "@vanilla/utils";

interface IProps {
    kbID?: number;
}

export function KnowledgeBaseAddEditUniversal(props: IProps) {
    const { form } = useKBData();
    const { updateForm } = useKnowledgeBaseActions();
    const allowedKBs = useAllowedUniversalTargets();
    const allowedIDs = allowedKBs.map(kb => kb.knowledgeBaseID).sort();

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
                        let newTargetIDs: number[] = [];
                        if (isUniversalSource) {
                            if (form.universalTargetIDs.length === 0) {
                                // Select all.
                                newTargetIDs = allowedIDs;
                            } else {
                                // Otherwise restore the previous values.
                                newTargetIDs = form.universalTargetIDs;
                            }
                        }
                        updateForm({
                            isUniversalSource,
                            universalTargetIDs: newTargetIDs,
                        });
                    }}
                />
            </DashboardFormGroup>
            <DashboardFormList isBlurred={!form.isUniversalSource}>
                <li>
                    <KBTableEditForm
                        targetIDs={form.universalTargetIDs}
                        onTargetIDsChange={targetIDs => {
                            updateForm({ universalTargetIDs: targetIDs });
                        }}
                    />
                </li>
            </DashboardFormList>
        </FrameBodyContainer>
    );
}

function KBTableEditForm(props: { targetIDs: number[]; onTargetIDsChange: (targetIDs: number[]) => void }) {
    const { form } = useKBData();
    const allowedKBs = useAllowedUniversalTargets();
    const allowedIDs = allowedKBs.map(kb => kb.knowledgeBaseID).sort();
    const [nameFilter, setNameFilter] = useState("");

    // Other universal sources cannot become targets.
    // We also shouldn't display ourself.
    const displayedKBs = allowedKBs.filter(kb => {
        // Apply name filtering
        if (nameFilter) {
            const normalizedFilter = normalizeString(nameFilter).toLocaleLowerCase();
            const normalizedName = normalizeString(kb.name).toLocaleLowerCase();
            const normalizedDesc = normalizeString(kb.description).toLocaleLowerCase();
            if (!normalizedName.includes(normalizedFilter) && !normalizedDesc.includes(normalizedFilter)) {
                return false;
            }
        }

        return true;
    });

    const targetIDSet = new Set(props.targetIDs.slice().sort());
    const displayedIDs = displayedKBs.map(kb => kb.knowledgeBaseID).sort();

    let isAllChecked = true;
    displayedIDs.forEach(displayedID => {
        if (!targetIDSet.has(displayedID)) {
            isAllChecked = false;
        }
    });

    const classes = knowledgeBaseAddEditClasses();

    return (
        <>
            <div className={classes.searchContainer}>
                <SearchBar
                    disableAutocomplete
                    value={nameFilter}
                    onChange={setNameFilter}
                    className={classes.searchBar}
                    valueContainerClasses={classNames(inputClasses().text)}
                    hideSearchButton
                    placeholder={t("Search")}
                />
                <span className={classes.searchMeta}>
                    <Translate source="<0/> Selected" c0={form.universalTargetIDs.length} />
                </span>
            </div>
            <DashboardTable
                head={
                    <tr>
                        <DashboardTable.HeadItem size={TableColumnSize.XL}>Knowledge Base</DashboardTable.HeadItem>
                        <DashboardTable.HeadItem>
                            <CheckBox
                                tooltipLabel
                                label={
                                    isAllChecked ? (
                                        <Translate source="Unselect <0/> Items" c0={displayedIDs.length} />
                                    ) : (
                                        <Translate source="Select <0/> Items" c0={displayedIDs.length} />
                                    )
                                }
                                checked={isAllChecked}
                                onChange={e => {
                                    if (e.target.checked) {
                                        const newSet = new Set(targetIDSet);
                                        displayedIDs.forEach(displayedID => {
                                            newSet.add(displayedID);
                                        });
                                        props.onTargetIDsChange(Array.from(newSet));
                                    } else {
                                        const clearedSet = Array.from(targetIDSet).filter(
                                            targetID => !displayedIDs.includes(targetID),
                                        );
                                        props.onTargetIDsChange(clearedSet);
                                    }
                                }}
                            />
                        </DashboardTable.HeadItem>
                    </tr>
                }
                body={displayedKBs.map(kb => {
                    return (
                        <tr key={kb.knowledgeBaseID}>
                            <td>
                                <DashboardMediaItem title={kb.name} info={kb.description} imgSrc={kb.icon} />
                            </td>
                            <td>
                                <DashboardTableOptions>
                                    <CheckBox
                                        label=""
                                        onChange={e => {
                                            const set = new Set(props.targetIDs);
                                            if (e.target.checked) {
                                                set.add(kb.knowledgeBaseID);
                                            } else {
                                                set.delete(kb.knowledgeBaseID);
                                            }
                                            props.onTargetIDsChange(Array.from(set));
                                        }}
                                        checked={props.targetIDs.includes(kb.knowledgeBaseID)}
                                    />
                                </DashboardTableOptions>
                            </td>
                        </tr>
                    );
                })}
            ></DashboardTable>
        </>
    );
}
