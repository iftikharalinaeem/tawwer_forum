/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import RadioButtonsAsTabs from "@library/components/radioButtonsAsTabs/radioButtonsAsTabs";
import RadioButtonTab from "@library/components/radioButtonsAsTabs/RadioButtonTab";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import Tokens from "@library/components/forms/select/Tokens";
import { dummyAuthors } from "@knowledge/modules/search/state/dummyAuthors";
import { IComboBoxOption } from "@library/components/forms/select/BigSearch";
import Checkbox from "@library/components/forms/Checkbox";
import SelectOne from "@library/components/forms/select/SelectOne";
import { dummyDateWithin } from "@knowledge/modules/search/state/dummyDateWithin";

export enum ISearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export enum ISearchWithin {
    ONE_DAY = "1 day",
    THREE_DAYS = "3 days",
    ONE_WEEK = "1 week",
    TWO_WEEKS = "2 weeks",
    ONE_MONTH = "1 month",
    SIX_MONTHS = "6 months",
    ONE_YEAR = "1 year",
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

    private setTitle = (title: IComboBoxOption): void => {
        this.props.setTitle(title.label);
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

    public render() {
        return (
            <form className="advancedSearch" onSubmit={doNothing}>
                <Heading>{t("AdvancedSearch")}</Heading>
                {/*<RadioButtonsAsTabs*/}
                {/*selectedTab={this.props.domain || ISearchDomain.ARTICLES}*/}
                {/*accessibleTitle={t("Search in:")}*/}
                {/*prefix="advancedSearchDomain"*/}
                {/*setData={this.props.setDomain}*/}
                {/*/>*/}
                {<InputTextBlock label={t("Title")} onChange={this.setTitle} value={this.props.title} />}
                {/*<Tokens label={t("Author")} options={dummyAuthors} setAuthor={this.setAuthor}/>*/}
                {/*<DateRange label={t("Author")} options={dummyAuthors} setAuthor={this.setAuthor}/>*/}
                <Checkbox
                    label={t("Deleted Articles")}
                    onChange={this.handleCheckBoxDeletedArticleChange}
                    checked={this.props.deletedArticles}
                />
            </form>
        );
    }
}
