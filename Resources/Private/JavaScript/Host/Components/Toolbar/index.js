import React, {PropTypes} from 'react';
import style from './style.css';

import IconButton from '../IconButton/';

const subComponentMap = {
    ICON_BUTTON: (options, onAction) => <IconButton {...options} onClick={onAction} />
};

const Toolbar = props => <div className={style.toolbar}>
    {props.configuration.map(componentConfiguration => {
        const {type} = componentConfiguration;

        if (subComponentMap[type] === undefined) {
            console.warn(`Could not find a matching toolbar subcomponent for ${type}`);
            return null;
        }

        return subComponentMap[type](
            componentConfiguration.options,
            () => props.onAction(props.uuid, type)
        );
    })}
</div>;
Toolbar.displayName = 'Toolbar';
Toolbar.propTypes = {
    configuration: PropTypes.array.isRequired,
    uuid: PropTypes.string.isRequired,

    onAction: PropTypes.func.isRequired
};

export default Toolbar;
