/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import InputTextBlock from "@library/components/forms/InputTextBlock";
import Checkbox from "@library/components/forms/Checkbox";
import Button from "@library/components/forms/Button";
import { connect } from "react-redux";
import SearchPageModel, { ISearchPageState, SearchDomain } from "@knowledge/modules/search/SearchPageModel";
import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import DateRange from "@knowledge/modules/search/components/DateRange";
import MultiUserInput from "@library/users/MultiUserInput";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import ButtonLoader from "@library/components/ButtonLoader";
import { LoadStatus } from "@library/@types/api";
import Permission from "@library/users/Permission";
import classNames from "classnames";
import TabContext from "library/src/scripts/contexts/TabContext";
import RadioButtonsAsTabs from "@library/components/radioButtonsAsTabs/RadioButtonsAsTabs";
import RadioButtonTab from "@library/components/radioButtonsAsTabs/RadioButtonTab";

export interface IProps extends ISearchFormActionProps, ISearchPageState {
    hideTitle?: boolean;
}

/**
 * Implements the search bar component
 */
export class AdvancedSearch extends React.Component<IProps> {
    public render() {
        const formData = this.props.form;

        return (
            <form className="advancedSearch" onSubmit={this.handleSubmit}>
                {!this.props.hideTitle && (
                    <Heading className="advancedSearch-title pageSubTitle" title={t("Advanced Search")} />
                )}
                <RadioButtonsAsTabs
                    accessibleTitle={t("Search in:")}
                    prefix="advancedSearchDomain"
                    setData={this.handleDomainChange}
                    activeTab={this.props.form.domain || SearchDomain.EVERYWHERE}
                    childClass="advancedSearchDomain-tab"
                >
                    <RadioButtonTab label={t("Articles")} data={SearchDomain.ARTICLES} />
                    <RadioButtonTab label={t("Everywhere")} data={SearchDomain.EVERYWHERE} />
                </RadioButtonsAsTabs>
                <InputTextBlock
                    label={t("Title")}
                    inputProps={{
                        onChange: this.handleTitleChange,
                        value: formData.title,
                    }}
                />
                <MultiUserInput onChange={this.handleUserChange} value={this.props.form.authors} />
                <DateRange
                    onStartChange={this.handleStartDateChange}
                    onEndChange={this.handleEndDateChange}
                    start={this.props.form.startDate}
                    end={this.props.form.endDate}
                />
                <Permission permission="articles.add">
                    <Checkbox
                        label={t("Deleted Articles")}
                        onChange={this.handleCheckBoxDeletedArticleChange}
                        checked={formData.includeDeleted}
                        className="inputBlock"
                    />
                </Permission>
                <Button type="submit" className="advancedSearch-submit" prefix="submitButton">
                    {this.props.results.status === LoadStatus.LOADING ? <ButtonLoader /> : t("Search")}
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
