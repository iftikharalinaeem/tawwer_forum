/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { useContext, useState, useEffect } from "react";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { connect } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import getStore from "@library/redux/getStore";

interface INavHistoryContext {
    lastKbID: number | null;
    lastKB: IKnowledgeBase | null;
    setLastKbID: (id: number | null) => void;
}

const NavHistoryContext = React.createContext<INavHistoryContext>({
    lastKbID: null,
    lastKB: null,
    setLastKbID: () => {
        return;
    },
});

export function useNavHistory() {
    return useContext(NavHistoryContext);
}

export function NavHistoryContextProvider(props: IProviderProps) {
    const [lastKbID, setLastKbID] = useState<number | null>(null);
    const { knowledgeBasesByID } = getStore<IKnowledgeAppStoreState>().getState().knowledge.knowledgeBases;
    const knowledgeBase = lastKbID !== null && knowledgeBasesByID.data ? knowledgeBasesByID.data[lastKbID] : null;

    return (
        <NavHistoryContext.Provider
            value={{
                lastKbID,
                setLastKbID,
                lastKB: knowledgeBase,
            }}
        >
            {props.children}
        </NavHistoryContext.Provider>
    );
}

interface IProviderProps {
    children: React.ReactNode;
}

interface IUpdaterProps {
    lastKbID?: number | null;
}

export function NavHistoryUpdater(props: IUpdaterProps) {
    const { setLastKbID } = useNavHistory();
    useEffect(() => {
        if (props.lastKbID != null) {
            setLastKbID(props.lastKbID);
        }
    }, [props.lastKbID, setLastKbID]);

    return null;
}
