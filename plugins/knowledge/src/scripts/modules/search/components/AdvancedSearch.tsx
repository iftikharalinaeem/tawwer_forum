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

export enum ISearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export enum ISearchWithin {
    ONE_DAY = "1 day",
    THREE_DAY = "3 days",
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
    setAuthor: (author: string[]) => void;
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

    private setAuthor = (author: string[]): void => {
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

    public render() {
        return (
            <form className="advancedSearch" onSubmit={doNothing}>
                <Heading>{t("AdvancedSearch")}</Heading>
                {<InputTextBlock label={t("Title")} onChange={this.setTitle} value={this.props.title} />}
            </form>
        );
    }
}

/*
    <RadioButtonsAsTabs
        selectedTab={this.props.domain || ISearchDomain.ARTICLES}
        accessibleTitle={t("Search in:")}
        prefix="advancedSearchDomain"
        setData={this.props.setDomain}
    >
        {<RadioButtonTab}
        {render={{ label: t("Articles"), setData: this.props.setDomain, data: ISearchDomain.ARTICLES }}}
        {/>}
    </RadioButtonsAsTabs>
*/
