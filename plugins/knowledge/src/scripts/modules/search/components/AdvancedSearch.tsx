/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import SearchPageModel, { ISearchPageState, SearchDomain } from "@knowledge/modules/search/SearchPageModel";
import { LoadStatus } from "@library/@types/api/core";
import { t } from "@library/utility/appUtils";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Button from "@library/forms/Button";
import Checkbox from "@library/forms/Checkbox";
import InputTextBlock from "@library/forms/InputTextBlock";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Heading from "@library/layout/Heading";
import RadioTabs from "@library/forms/radioTabs/RadioTabs";
import MultiUserInput from "@library/features/users/MultiUserInput";
import Permission from "@library/features/users/Permission";
import * as React from "react";
import { connect } from "react-redux";
import { globalVariables } from "@library/styles/globalStyleVars";
import classNames from "classnames";
import { buttonClasses } from "@library/forms/buttonStyles";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import DateRange from "@library/forms/DateRange";
import CommunityCategoryInput from "@vanilla/forms/CommunityCategoryInput";
import KnowledgeBaseInput from "@knowledge/knowledge-bases/KnowledgeBaseInput";
import RadioTab from "@library/forms/radioTabs/RadioTab";

export interface IProps extends ISearchFormActionProps, ISearchPageState {
    hideTitle?: boolean;
}

/**
 * Implements the search bar component
 */
export class AdvancedSearch extends React.Component<IProps> {
    public render() {
        const formData = this.props.form;
        const vars = globalVariables();
        const buttons = buttonClasses();
        const classesDateRange = dateRangeClasses();
        return (
            <form className="advancedSearch" onSubmit={this.handleSubmit}>
                {!this.props.hideTitle && (
                    <Heading className="advancedSearch-title pageSubTitle" title={t("Filter Results")} />
                )}
                <RadioTabs
                    accessibleTitle={t("Search in:")}
                    prefix="advancedSearchDomain"
                    setData={this.handleDomainChange}
                    activeTab={this.props.form.domain || SearchDomain.EVERYWHERE}
                    childClass="advancedSearchDomain-tab"
                >
                    <RadioTab label={t("Articles")} position="left" data={SearchDomain.ARTICLES} />
                    <RadioTab label={t("Everywhere")} position="right" data={SearchDomain.EVERYWHERE} />
                </RadioTabs>
                <InputTextBlock
                    label={t("Title")}
                    inputProps={{
                        onChange: this.handleTitleChange,
                        value: formData.title,
                    }}
                />
                <MultiUserInput onChange={this.handleUserChange} value={this.props.form.authors} />
                {this.props.form.domain === SearchDomain.EVERYWHERE && (
                    <CommunityCategoryInput
                        className="inputBlock"
                        onChange={this.handleCommunityCategoryChange}
                        value={this.props.form.communityCategory}
                    />
                )}
                {this.props.form.domain === SearchDomain.ARTICLES && (
                    <KnowledgeBaseInput
                        className="inputBlock"
                        onChange={this.handleKnowledgeBaseChange}
                        value={this.props.form.kb}
                    />
                )}
                <DateRange
                    onStartChange={this.handleStartDateChange}
                    onEndChange={this.handleEndDateChange}
                    start={this.props.form.startDate}
                    end={this.props.form.endDate}
                    className={classesDateRange.root}
                />
                <Permission permission="articles.add">
                    <Checkbox
                        label={t("Deleted Articles")}
                        onChange={this.handleCheckBoxDeletedArticleChange}
                        checked={formData.includeDeleted}
                        className="inputBlock"
                    />
                </Permission>
                <Button
                    type="submit"
                    className={classNames("advancedSearch-submit", buttons.standard)}
                    prefix="submitButton"
                >
                    {this.props.results.status === LoadStatus.LOADING ? <ButtonLoader /> : t("Filter")}
                </Button>
            </form>
        );
    }

    /**
     * Handler for the deleted articles checkbox.
     */
    private handleCheckBoxDeletedArticleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        this.props.searchActions.updateForm({ includeDeleted: event.target.checked || false });
    };

    /**
     * Simple form setter.
     */
    private handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.props.searchActions.updateForm({ title: value });
    };

    /**
     * Simple form setter.
     */
    private handleStartDateChange = (date: string) => {
        this.props.searchActions.updateForm({ startDate: date });
    };

    /**
     * Simple form setter.
     */
    private handleEndDateChange = (date: string) => {
        this.props.searchActions.updateForm({ endDate: date });
    };

    /**
     * Simple form setter.
     */
    private handleUserChange = (options: IComboBoxOption[]) => {
        this.props.searchActions.updateForm({ authors: options });
    };

    /**
     * Handle changes to the Community Category filter.
     *
     * @param option New value for the filter.
     */
    private handleCommunityCategoryChange = (option: IComboBoxOption) => {
        this.props.searchActions.updateForm({ communityCategory: option });
    };

    /**
     * Handle changes to the Knowledge Base filter.
     *
     * @param option New value for the filter.
     */
    private handleKnowledgeBaseChange = (option: IComboBoxOption) => {
        this.props.searchActions.updateForm({ kb: option });
    };

    /**
     * Simple form setter.
     */
    private handleDomainChange = (domain: SearchDomain) => {
        this.props.searchActions.updateForm({ domain });
    };

    /**
     * Handle the form submission by triggering a search.
     */
    private handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        return this.props.searchActions.search();
    };
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(AdvancedSearch);
