/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import { t } from "@library/utility/appUtils";
import React from "react";
import { IKnowledgeBase, KnowledgeBaseStatus } from "./KnowledgeBaseModel";
import { useKnowledgeBases } from "@knowledge/knowledge-bases/knowledgeBaseHooks";

interface IProps extends ISelectOneProps {
    className?: string;
    placeholder?: string;
}

export default function KnowledgeBaseInput(props: IProps) {
    const knowledgeBasesByID = useKnowledgeBases(KnowledgeBaseStatus.PUBLISHED);

    const kbOptions: IComboBoxOption[] = [];

    if (knowledgeBasesByID.status === LoadStatus.SUCCESS && knowledgeBasesByID.data) {
        Object.keys(knowledgeBasesByID.data).forEach(knowledgeBaseID => {
            const knowledgeBase: IKnowledgeBase = knowledgeBasesByID.data![knowledgeBaseID];
            kbOptions.push({
                label: knowledgeBase.name,
                value: knowledgeBase.knowledgeBaseID,
            });
        });
    }

    kbOptions.sort((a, b) => {
        if (a === b) {
            return 0;
        } else {
            return a.label > b.label ? 1 : -1;
        }
    });

    return <SelectOne {...props} options={kbOptions} />;
}
