import './page/sw-ai-assistant-index';

Shopware.Module.register('sw-ai-assistant', {
    type: 'plugin',
    name: 'AiAssistant',
    title: 'sw-ai-assistant.general.mainMenuItemGeneral',
    description: 'sw-ai-assistant.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'default-object-lab-flask',

    routes: {
        index: {
            component: 'sw-ai-assistant-index',
            path: 'index'
        }
    },

    navigation: [{
        label: 'sw-ai-assistant.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'sw.ai.assistant.index',
        icon: 'default-object-lab-flask',
        parent: 'sw-catalogue',
        position: 100
    }]
});
