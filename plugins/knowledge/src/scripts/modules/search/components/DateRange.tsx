/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import classNames from "classnames";
import InputBlock, { InputTextBlockBaseClass } from "@library/components/forms/InputBlock";
import { t } from "@library/application";
import DateInput from "@library/components/forms/DateInput";
import moment from "moment";
import { style } from "typestyle";

interface IProps {
    start: string | undefined;
    end: string | undefined;
    onStartChange: (value: string) => void;
    onEndChange: (value: string) => void;
    className?: string;
}

interface IState {}

/**
 * Implements the DateRange component
 */
export default class DateRange extends React.PureComponent<IProps> {
    public render() {
        const endDate = this.props.end ? moment(this.props.end).toDate() : undefined;
        const startDate = this.props.start ? moment(this.props.start).toDate() : undefined;
        const fromLabel = t("From");
        // const fromLabel = t("From FromFromFromFrom FromFromFromFrom FromFromFromFromFromFrom");
        const toLabel = t("To");
        const longestLabelCount = (fromLabel.length >= toLabel.length ? fromLabel.length : toLabel.length) + 4; // offset
        const labelPadding = 4;
        const labelTooLong = longestLabelCount > 12;

        const labelStyles = style({
            width: labelTooLong ? "100%" : `calc(${longestLabelCount}ex + ${labelPadding}px)`,
            maxWidth: "100%",
            paddingRight: `${labelPadding}px`,
        });
        const inputStyles = style({
            width: labelTooLong ? "100%" : `calc(100% - calc(${longestLabelCount}ex + ${labelPadding * 2}px))`,
            minWidth: "136px",
            maxWidth: "100%",
            flexGrow: 1,
        });
        const dateBlockOffset = style({
            marginLeft: labelPadding,
        });
        return (
            <fieldset className={classNames("dateRange", "inputBlock", this.props.className)}>
                <legend className="inputBlock-labelText dateUpdated-label">{t("Date Updated")}</legend>
                <label className={classNames("dateRange-dateBlock", dateBlockOffset)}>
                    <span className={classNames("dateRange-label", labelStyles)}>{fromLabel}</span>
                    <DateInput
                        alignment="right"
                        // inputClassName={inputStyles}
                        contentClassName={inputStyles}
                        onChange={this.props.onStartChange}
                        value={this.props.start}
                        disabledDays={[
                            {
                                after: endDate,
                            },
                        ]}
                    />
                </label>
                <label className={classNames("dateRange-dateBlock", dateBlockOffset)}>
                    <span className={classNames("dateRange-label", labelStyles)}>{toLabel}</span>
                    <DateInput
                        alignment="right"
                        // inputClassName={inputStyles}
                        contentClassName={inputStyles}
                        onChange={this.props.onEndChange}
                        value={this.props.end}
                        disabledDays={[
                            {
                                before: startDate,
                            },
                        ]}
                    />
                </label>
            </fieldset>
        );
    }
}
