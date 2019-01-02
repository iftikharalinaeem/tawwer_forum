/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import classNames from "classnames";
import InputBlock, { InputTextBlockBaseClass } from "@library/components/forms/InputBlock";
import { t } from "@library/application";
import DateInput from "@library/components/forms/DateInput";
import moment from "moment";

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

        return (
            <div className={classNames("dateRange inputBlock", this.props.className)}>
                <div className="dateRange-row">
                    <InputBlock
                        label={t("Date from")}
                        baseClass={InputTextBlockBaseClass.CUSTOM}
                        className="dateRange-column"
                    >
                        <DateInput
                            alignment="right"
                            onChange={this.props.onStartChange}
                            value={this.props.start}
                            disabledDays={[
                                {
                                    after: endDate,
                                },
                            ]}
                        />
                    </InputBlock>
                    <InputBlock label={t("To")} baseClass={InputTextBlockBaseClass.CUSTOM} className="dateRange-column">
                        <DateInput
                            alignment="right"
                            onChange={this.props.onEndChange}
                            value={this.props.end}
                            disabledDays={[
                                {
                                    before: startDate,
                                },
                            ]}
                        />
                    </InputBlock>
                </div>
            </div>
        );
    }
}
