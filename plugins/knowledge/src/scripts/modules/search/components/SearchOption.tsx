/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DateTime from "@library/components/DateTime";
import BreadCrumbString from "@library/components/BreadCrumbString";
import { OptionProps } from "react-select/lib/components/Option";
import { ISearchResult } from "@knowledge/@types/api";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import { SelectOption } from "@library/components/forms/select/overwrites";

interface IProps extends OptionProps<any> {
    data: IComboBoxOption<ISearchResult>;
}

/**
 */
export default function SearchOption(props: IProps) {
    const { data, innerProps } = props;

    if (data.data) {
        const { dateUpdated, knowledgeCategory } = data.data!;
        const hasLocationData = knowledgeCategory && knowledgeCategory.breadcrumbs.length > 0;
        return (
            <li {...innerProps} className="suggestedTextInput-item">
                <button
                    type="button"
                    title={props.label}
                    aria-label={props.label}
                    className="suggestedTextInput-option"
                >
                    <span className="suggestedTextInput-head">
                        <span className="suggestedTextInput-title">{props.children}</span>
                    </span>
                    <span className="suggestedTextInput-main">
                        <span className="metas isFlexed">
                            {dateUpdated && (
                                <span className="meta">
                                    <DateTime className="meta" timestamp={dateUpdated} />
                                </span>
                            )}
                            {hasLocationData && (
                                <BreadCrumbString className="meta" crumbs={knowledgeCategory!.breadcrumbs} />
                            )}
                        </span>
                    </span>
                </button>
            </li>
        );
    } else {
        return <SelectOption {...props} />;
    }
}
