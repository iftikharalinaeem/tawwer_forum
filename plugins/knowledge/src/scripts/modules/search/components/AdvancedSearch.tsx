/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import InputTextBlock from "@library/components/forms/InputTextBlock";
import Tokens from "@library/components/forms/select/Tokens";
import { dummyAuthors } from "@knowledge/modules/search/state/dummyAuthors";
import Checkbox from "@library/components/forms/Checkbox";
import SelectOne from "@library/components/forms/select/SelectOne";
import DateRange from "@knowledge/modules/search/components/DateRange";
import RadioButtonsAsTabs from "@library/components/radioButtonsAsTabs/RadioButtonsAsTabs";
import RadioButtonTab from "@library/components/radioButtonsAsTabs/RadioButtonTab";
import { ISearchWithin } from "@knowledge/modules/search/state/dateWithin";
import { dummyKnowledgeBaseList } from "@knowledge/modules/search/state/dummyKnowledgeBaseList";
import Button from "@library/components/forms/Button";
import { IComboBoxOption } from "@library/components/forms/select/BigSearch";

export enum ISearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export interface IAdvancedFields {
    domain: ISearchDomain;
    title: string;
    author: IComboBoxOption[];
    fileName: string;
    within: ISearchWithin;
    of: string;
    deletedArticles: boolean;
    kb?: IComboBoxOption;
}

interface IProps extends IAdvancedFields {
    setDomain: (domain: ISearchDomain) => void;
    setTitle: (title: string) => void;
    setAuthor: (author: IComboBoxOption[]) => void;
    setFileName: (fileName: string) => void;
    setWithin: (within: ISearchWithin) => void;
    setOf: (of: string) => void;
    setKnowedge?: (kb: IComboBoxOption) => void;
    setDeletedArticles: (deletedArticles: boolean) => void;
}

const doNothing = e => {
    e.preventDefault();
};

/**
 * Implements the search bar component
 */
export default class AdvancedSearch extends React.Component<IProps> {
    public static defaultProps = {
        domain: ISearchDomain.ARTICLES,
        deletedArticles: false,
    };

    /**
     * Handler for the deleted articles checkbox.
     */
    private handleCheckBoxDeletedArticleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.props.setDeletedArticles(event.target.checked || false);
    };

    /**
     * Handler for title field.
     */
    private handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.props.setTitle(value || "");
    };

    public render() {
        return (
            <form className="advancedSearch" onSubmit={doNothing}>
                <Heading className="advancedSearch-title pageSubTitle">{t("AdvancedSearch")}</Heading>
                <RadioButtonsAsTabs
                    accessibleTitle={t("Search in:")}
                    prefix="advancedSearchDomain"
                    setData={this.props.setDomain}
                    defaultTab={ISearchDomain.ARTICLES}
                    childClass="advancedSearchDomain-tab"
                >
                    <RadioButtonTab label={t("Articles")} data={ISearchDomain.ARTICLES} />
                    <RadioButtonTab label={t("Everywhere")} data={ISearchDomain.EVERYWHERE} />
                </RadioButtonsAsTabs>
                {<InputTextBlock label={t("Title")} onChange={this.handleTitleChange} value={this.props.title} />}
                <Tokens label={t("Author")} options={dummyAuthors} setAuthor={this.props.setAuthor} />
                <DateRange
                    className="inputBlock"
                    within={this.props.within}
                    of={this.props.of}
                    setOf={this.props.setOf}
                    setWithin={this.props.setWithin}
                />
                {dummyKnowledgeBaseList &&
                    dummyKnowledgeBaseList.length > 0 && (
                        <SelectOne
                            label={t("Knowledge Base")}
                            className="inputBlock dateRange-within"
                            options={dummyKnowledgeBaseList}
                            setData={this.props.setKnowedge!}
                        />
                    )}
                <Checkbox
                    label={t("Deleted Articles")}
                    onChange={this.handleCheckBoxDeletedArticleChange}
                    checked={this.props.deletedArticles}
                    className="inputBlock"
                />
                <Button type="submit" className="advancedSearch-submit" prefix="submitButton">
                    {t("Search")}
                </Button>
            </form>
        );
    }
}
