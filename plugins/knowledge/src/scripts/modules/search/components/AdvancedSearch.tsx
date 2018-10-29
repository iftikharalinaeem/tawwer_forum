/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import Tokens from "@library/components/forms/select/Tokens";
import { dummyAuthors } from "@knowledge/modules/search/state/dummyAuthors";
import { IComboBoxOption } from "@library/components/forms/select/BigSearch";
import Checkbox from "@library/components/forms/Checkbox";
import SelectOne from "@library/components/forms/select/SelectOne";
import DateRange from "@knowledge/modules/search/components/DateRange";
import RadioButtonsAsTabs from "@library/components/radioButtonsAsTabs/RadioButtonsAsTabs";
import RadioButtonTab from "@library/components/radioButtonsAsTabs/RadioButtonTab";
import { ISearchWithin } from "@knowledge/modules/search/state/dateWithin";
import { dummyKnowledgeBaseList } from "@knowledge/modules/search/state/dummyKnowledgeBaseList";
import ButtonSubmit from "@library/components/forms/ButtonSubmit";

export enum ISearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export interface IAdvancedFields {
    domain: ISearchDomain;
    title: string;
    author: any[]; // TBD in next PR
    fileName: string;
    within: ISearchWithin;
    of: string;
    deletedArticles: boolean;
}

interface IProps extends IAdvancedFields {
    setDomain: (domain: ISearchDomain) => void;
    setTitle: (title: string) => void;
    setAuthor: (author: IComboBoxOption[]) => void;
    setFileName: (fileName: string) => void;
    setWithin: (within: ISearchWithin) => void;
    setOf: (of: string) => void;
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

    private setDomain = (domain: ISearchDomain) => {
        this.props.setDomain(domain);
    };

    private setTitle = (title: string): void => {
        this.props.setTitle(title);
    };

    private setAuthor = (author: IComboBoxOption[]): void => {
        this.props.setAuthor(author);
    };

    private setFileName = (fileName: string): void => {
        this.props.setFileName(fileName);
    };

    private setWithin = (within: ISearchWithin): void => {
        this.props.setWithin(within);
    };

    private setOf = (of: string): void => {
        this.props.setOf(of);
    };
    private setDeletedArticles = (deletedArticles: boolean): void => {
        this.props.setDeletedArticles(deletedArticles);
    };

    /**
     * Handler for the deleted articles checkbox.
     */
    private handleCheckBoxDeletedArticleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.setDeletedArticles(event.target.checked || false);
    };

    /**
     * Handler for title field.
     */
    private handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setTitle(value || "");
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
                <Tokens label={t("Author")} options={dummyAuthors} setAuthor={this.setAuthor} />
                <DateRange
                    within={this.props.within}
                    of={this.props.of}
                    setOf={this.props.setOf}
                    setWithin={this.props.setWithin}
                />
                {dummyKnowledgeBaseList &&
                    dummyKnowledgeBaseList.length > 0 && (
                        <SelectOne
                            label={t("Knowledge Base")}
                            className="dateRange-within dateRange-column"
                            options={dummyKnowledgeBaseList}
                            setData={this.setWithin}
                        />
                    )}
                <Checkbox
                    label={t("Deleted Articles")}
                    onChange={this.handleCheckBoxDeletedArticleChange}
                    checked={this.props.deletedArticles}
                />
                <ButtonSubmit>{t("Search")}</ButtonSubmit>
            </form>
        );
    }
}
