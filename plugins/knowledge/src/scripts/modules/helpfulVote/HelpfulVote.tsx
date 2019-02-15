/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { style } from "typestyle";
import { px } from "csx";
import { connect } from "react-redux";
import get from "lodash/get";
import SmartLink from "@library/components/navigation/SmartLink";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";
import Button from "@library/components/forms/Button";
import Translate from "@library/components/translation/Translate";
import { t, formatUrl } from "@library/application";
import Heading from "@library/components/Heading";
import { IInjectableUserState } from "@library/users/UsersModel";
import UsersModel from "@library/users/UsersModel";

interface IProps extends IInjectableUserState {
    className?: string;
    theme?: object;
    positiveVotes: number;
    totalVotes: number;
    voted: boolean;
    yesAction: () => void;
    noAction: () => void;
}

/**
 * Implement mobile next/previous nav to articles
 */
export class HelpfulVote extends React.Component<IProps> {
    public helpfulVoteVariables = (theme?: object) => {
        const globalVars = globalVariables(theme);
        const themeVars = componentThemeVariables(theme, "helpful");

        const button = {
            minWidth: 96,
            margin: 6,
            ...themeVars.subComponentStyles("button"),
        };

        const title = {
            fontSize: globalVars.fonts.size.medium,
            fontWeight: globalVars.fonts.weights.bold,
            ...themeVars.subComponentStyles("title"),
        };

        const results = {
            fontSize: globalVars.fonts.size.small,
            color: globalVars.mixBgAndFg(0.85),
            ...themeVars.subComponentStyles("results"),
        };

        const spacing = {
            default: 12,
            topMargin: 24,
            ...themeVars.subComponentStyles("spacing"),
        };

        const signIn = {
            color: globalVars.links.color,
        };

        return { button, globalVars, title, results, spacing, signIn };
    };

    public helpfulVoteClasses = (theme?: object) => {
        const vars = this.helpfulVoteVariables(theme);
        const debug = debugHelper("helpful");

        const root = style({
            textAlign: "center",
            marginTop: px(vars.spacing.topMargin),
            ...debug.name(),
        });

        const title = style({
            display: "block",
            fontSize: px(vars.title.fontSize),
            fontWeight: vars.title.fontWeight,
            ...debug.name("title"),
        });
        const vote = style({
            display: "flex",
            justifyContent: "center",
            marginTop: px(vars.spacing.default),
            ...debug.name("vote"),
        });
        const result = style({
            color: vars.results.color.toString(),
            fontSize: px(vars.results.fontSize),
            marginTop: px(vars.spacing.default),
            ...debug.name("results"),
        });

        const guestMessage = style({
            ...debug.name("guestMessage"),
        });

        const signIn = style({
            marginTop: px(vars.spacing.default),
            ...debug.name("signIn"),
        });

        const button = style({
            minWidth: px(vars.button.minWidth),
            margin: `0 ${px(vars.button.margin)}`,
            ...debug.name("button"),
        });

        return { root, title, vote, result, signIn, button, guestMessage };
    };

    public render() {
        const { className, theme, positiveVotes, totalVotes, voted, yesAction, noAction, currentUser } = this.props;
        const classes = this.helpfulVoteClasses(theme);
        const isGuest = get(currentUser, "data.userID", 0) === 0;
        return (
            <section className={classNames(classes.root, className)}>
                {(isGuest || !voted) && <Heading title={t("Was this article helpful?")} className={classes.title} />}

                {!isGuest &&
                    !voted && (
                        <div className={classes.vote}>
                            <Button onClick={yesAction} className={classes.button}>
                                {t("Yes")}
                            </Button>
                            <Button onClick={noAction} className={classes.button}>
                                {t("No")}
                            </Button>
                        </div>
                    )}

                {/* Guest */}
                {isGuest && (
                    <p className={classes.guestMessage}>
                        <Translate
                            source="You need to <0/> to vote on this article"
                            c0={<SmartLink to={formatUrl("/authenticate/signin")}>{t("Sign In")}</SmartLink>}
                        />
                    </p>
                )}

                <p className={classes.result}>
                    <Translate source="<0/> out of <1/> people found this helpful" c0={positiveVotes} c1={totalVotes} />
                </p>
            </section>
        );
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(HelpfulVote);
