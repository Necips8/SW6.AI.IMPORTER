import template from './sw-ai-assistant-index.html.twig';

const { Component, Mixin } = Shopware;

Component.register('sw-ai-assistant-index', {
    template,

    inject: ['loginService'],

    data() {
        return {
            productName: '',
            publishImmediately: false,
            isLoading: false,
            result: null,
            error: null,

            batchJson: '',
            batchPublishImmediately: false,
            batchLoading: false,
            batchResult: null,

            drafts: [],
            draftsLoading: false,
            draftsLoaded: false,
            draftColumns: [
                { property: 'name', label: 'Name' },
                { property: 'productNumber', label: 'SKU' },
                { property: 'createdAt', label: 'Created' },
            ],
        };
    },

    computed: {
        httpClient() {
            return Shopware.Application.getContainer('init').httpClient;
        },
    },

    methods: {
        async onStartImport() {
            if (!this.productName) return;

            this.isLoading = true;
            this.result = null;
            this.error = null;

            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`,
            };

            try {
                const response = await this.httpClient.post(
                    '/_action/ai-assistant/import',
                    {
                        name: this.productName,
                        publish: this.publishImmediately,
                    },
                    { headers }
                );

                this.result = response.data;
                this.createNotificationSuccess({
                    title: 'AI Assistant',
                    message: 'Product successfully created!',
                });
            } catch (e) {
                this.error = e.response?.data?.error || e.message;
                this.createNotificationError({
                    title: 'AI Assistant',
                    message: this.error,
                });
            } finally {
                this.isLoading = false;
            }
        },

        async onBatchImport() {
            if (!this.batchJson) return;

            this.batchLoading = true;
            this.batchResult = null;

            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`,
            };

            try {
                const products = JSON.parse(this.batchJson);

                const response = await this.httpClient.post(
                    '/_action/ai-assistant/import-batch',
                    {
                        products: Array.isArray(products)
                            ? products.map(p => p.produktname || p.name)
                            : [products.produktname || products.name],
                        publish: this.batchPublishImmediately,
                    },
                    { headers }
                );

                this.batchResult = response.data;
                this.createNotificationSuccess({
                    title: 'AI Assistant',
                    message: `Batch import completed: ${response.data.successCount} success, ${response.data.failedCount} failed`,
                });
            } catch (e) {
                this.createNotificationError({
                    title: 'AI Assistant',
                    message: e.response?.data?.error || e.message,
                });
            } finally {
                this.batchLoading = false;
            }
        },

        async onLoadDrafts() {
            this.draftsLoading = true;

            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`,
            };

            try {
                const response = await this.httpClient.get(
                    '/_action/ai-assistant/drafts',
                    { headers }
                );
                this.drafts = response.data.drafts || [];
                this.draftsLoaded = true;
            } catch (e) {
                this.createNotificationError({
                    title: 'AI Assistant',
                    message: e.message,
                });
            } finally {
                this.draftsLoading = false;
            }
        },

        async onPublishAllDrafts() {
            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`,
            };

            try {
                const response = await this.httpClient.post(
                    '/_action/ai-assistant/publish-drafts',
                    {},
                    { headers }
                );
                this.createNotificationSuccess({
                    title: 'AI Assistant',
                    message: `${response.data.publishedCount} drafts published`,
                });
                await this.onLoadDrafts();
            } catch (e) {
                this.createNotificationError({
                    title: 'AI Assistant',
                    message: e.message,
                });
            }
        },

        async onPublishDraft(id) {
            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`,
            };

            try {
                await this.httpClient.post(
                    '/_action/ai-assistant/publish-drafts',
                    {},
                    { headers }
                );
                this.createNotificationSuccess({ title: 'AI Assistant', message: 'Draft published' });
                await this.onLoadDrafts();
            } catch (e) {
                this.createNotificationError({ title: 'AI Assistant', message: e.message });
            }
        },

        async onDeleteDraft(id) {
            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`,
            };

            try {
                await this.httpClient.delete(
                    `/_action/ai-assistant/delete-draft/${id}`,
                    { headers }
                );
                this.createNotificationSuccess({ title: 'AI Assistant', message: 'Draft deleted' });
                await this.onLoadDrafts();
            } catch (e) {
                this.createNotificationError({ title: 'AI Assistant', message: e.message });
            }
        },
    },

    mixins: [
        Mixin.getByName('notification'),
    ],
});
