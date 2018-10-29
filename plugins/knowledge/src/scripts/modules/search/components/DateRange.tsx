/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import SelectOne from "@library/components/forms/select/SelectOne";
import { help } from "@library/components/Icons";
import { ISearchWithin, dateWithin } from "../state/dateWithin";

interface IProps {
    within: ISearchWithin;
    setWithin: (within: ISearchWithin) => void;
    of: string;
    setOf: (of: string) => void;
}

interface IState {
    showHelp: boolean;
}

/**
 * Implements the DateRange component
 */
export default class DateRange extends React.PureComponent<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showHelp: false,
        };
    }

    /**
     * Change handler for date within
     */
    private setWithin = (newValue: any) => {
        this.props.setWithin(newValue);
    };

    /**
     * Change handler for of
     */
    private setOf = (newValue: any) => {
        this.props.setWithin(newValue);
    };

    private showHelp = () => {
        this.setState({
            showHelp: true,
        });
    };

    public render() {
        const ofLabelMessage = t("Examples: Monday, today, last week, Mar 26, 3/26/04");
        const ofLabel = (
            <React.Fragment>
                <span className="dateRangeOfLabel-label">{t("Of")}</span>
                <span className="dateRangeOfLabel-help" title={ofLabelMessage} onClick={this.showHelp}>
                    {help()}
                    <span className="sr-only">{ofLabelMessage}</span>
                </span>
            </React.Fragment>
        );
        return (
            <div className="dateRange">
                <SelectOne
                    label={t("Date Within")}
                    className="dateRange-within dateRange-column"
                    options={dateWithin}
                    setData={this.setWithin}
                />
                <InputTextBlock
                    className="dateRange-of dateRange-column"
                    label={ofLabel}
                    labelClassName="dateRangeOfLabel"
                    onChange={this.setOf}
                    value={this.props.of}
                    noteAfterInput={this.state.showHelp ? ofLabelMessage : undefined}
                />
            </div>
        );
    }
}
