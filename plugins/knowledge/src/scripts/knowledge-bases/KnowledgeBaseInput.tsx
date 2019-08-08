/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import { t } from "@library/utility/appUtils";
import React from "react";
import { connect } from "react-redux";
import { IKnowledgeBase } from "./KnowledgeBaseModel";

interface IProps extends ISelectOneProps {
    className?: string;
    placeholder?: string;
}

/**
 * Form component for searching/selecting a category.
 */
export class KnowledgeBaseInput extends React.Component<IProps> {
    public static defaultProps = {
        label: t("Knowledge Base"),
        options: [],
        value: undefined,
        placeholder: "",
    };

    public render() {
        return <SelectOne {...this.props} />;
    }
}

function mapStateToProps(state: IStoreState, ownProps: ISelectOneProps) {
    const { knowledgeBasesByID } = state.knowledge.knowledgeBases;
    const options: IComboBoxOption[] = [];

    if (knowledgeBasesByID.status === LoadStatus.SUCCESS && knowledgeBasesByID.data) {
        Object.keys(knowledgeBasesByID.data).forEach(knowledgeBaseID => {
            const knowledgeBase: IKnowledgeBase = knowledgeBasesByID.data![knowledgeBaseID];
            options.push({
                label: knowledgeBase.name,
                value: knowledgeBase.knowledgeBaseID,
            });
        });
    }

    options.sort((a, b) => {
        if (a === b) {
            return 0;
        } else {
            return a.label > b.label ? 1 : -1;
        }
    });
    return { options };
}

export default connect(mapStateToProps)(KnowledgeBaseInput);
