import {
    reducer as FlashMessagesReducer,
    initialState as FlashMessagesInitialState,
    actions as FlashMessages
} from './FlashMessages/';
import {
    reducer as FullScreenReducer,
    initialState as FullScreenInitialState,
    actions as FullScreen
} from './FullScreen/';
import {
    reducer as LeftSideBarReducer,
    initialState as LeftSideBarInitialState,
    actions as LeftSideBar
} from './LeftSideBar/';
import {
    reducer as OffCanvasReducer,
    initialState as OffCanvasInitialState,
    actions as OffCanvas
} from './OffCanvas/';
import {
    reducer as RemoteReducer,
    initialState as RemoteInitialState,
    actions as Remote
} from './Remote/';
import {
    reducer as RightSideBarReducer,
    initialState as RightSideBarInitialState,
    actions as RightSideBar
} from './RightSideBar/';
import {
    reducer as AddNodeModalReducer,
    initialState as AddNodeModalInitialState,
    actions as AddNodeModal
} from './AddNodeModal/';
import {
    reducer as PageTreeReducer,
    initialState as PageTreeInitialState,
    actionTypes as PageTreeActionTypes,
    actions as PageTree
} from './PageTree/';
import {
    reducer as ContentViewReducer,
    initialState as ContentViewInitialState,
    actionTypes as ContentViewActionTypes,
    actions as ContentView
} from './ContentView/';

//
// Export the action types
//
export const actionTypes = {
    PageTree: PageTreeActionTypes,
    ContentView: ContentViewActionTypes
};

//
// Export the actions
//
export const actions = {
    FlashMessages,
    FullScreen,
    LeftSideBar,
    OffCanvas,
    Remote,
    RightSideBar,
    AddNodeModal,
    PageTree,
    ContentView
};

//
// Export the initial state
//
export const initialState = {
    flashMessages: FlashMessagesInitialState,
    fullScreen: FullScreenInitialState,
    leftSideBar: LeftSideBarInitialState,
    offCanvas: OffCanvasInitialState,
    remote: RemoteInitialState,
    rightSideBar: RightSideBarInitialState,
    addNodeModal: AddNodeModalInitialState,
    pageTree: PageTreeInitialState,
    contentView: ContentViewInitialState
};

//
// Export the reducer
//
export const reducer = {
    ...FlashMessagesReducer,
    ...FullScreenReducer,
    ...LeftSideBarReducer,
    ...OffCanvasReducer,
    ...RemoteReducer,
    ...RightSideBarReducer,
    ...AddNodeModalReducer,
    ...PageTreeReducer,
    ...ContentViewReducer
};
