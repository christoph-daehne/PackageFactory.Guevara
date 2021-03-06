import createEditor from 'Guest/Components/Editors/CreateEditor/';

export const editor = ckApi => {
    if (!ckApi) {
        console.error('CKEditor not found!');

        //
        // Return noop to not break things
        //
        return () => {};
    }

    ckApi.disableAutoInline = true;

    return createEditor(
        (config, api, dom) => {
            const ckInstance = ckApi.inline(dom, {
                removePlugins: 'toolbar',
                allowedContent: true
            });

            ckInstance.on('change', () => api.commit(ckInstance.getData()));
        }
    );
};

export default editor(window.CKEDITOR);
