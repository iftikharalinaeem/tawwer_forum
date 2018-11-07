/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import { t } from "@library/application";
import InputTextBlock, { InputTextBlockBaseClass } from "@library/components/forms/InputTextBlock";
import SelectOne from "@library/components/forms/select/SelectOne";
import { help } from "@library/components/icons/common";
import { ISearchWithin, dateWithin } from "../state/dateWithin";
import classNames from "classnames";

interface IProps {
    within: ISearchWithin;
    setWithin: (within: ISearchWithin) => void;
    of: string;
    setOf: (of: string) => void;
    className?: string;
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

    private setOf = e => {
        this.props.setOf(e.value);
    };

    private showHelp = e => {
        this.setState({
            showHelp: true,
        });
    };

    public render() {
        const ofLabelMessage = t("Examples: Monday, today, last week, Mar 26, 3/26/04");
        const ofLabel = (
            <React.Fragment>
                <span className="dateRangeOfLabel-label">{t("of")}</span>
                <span className="dateRangeOfLabel-help" title={ofLabelMessage} onClick={this.showHelp}>
                    {help()}
                    <span className="sr-only">{ofLabelMessage}</span>
                </span>
            </React.Fragment>
        );
        return (
            <div className={classNames("dateRange", this.props.className)}>
                <div className="dateRange-row">
                    <SelectOne
                        label={t("Date Within")}
                        className="dateRange-within dateRange-column"
                        options={dateWithin}
                        setData={this.props.setWithin}
                    />
                    <InputTextBlock
                        className="dateRange-of dateRange-column"
                        baseClass={InputTextBlockBaseClass.CUSTOM}
                        label={ofLabel}
                        labelClassName="dateRangeOfLabel"
                        onChange={this.setOf}
                        value={this.props.of}
                        noteAfterInput={this.state.showHelp ? ofLabelMessage : undefined}
                    />
                </div>
            </div>
        );
    }
}
