(function () {
    'use strict'

    BX.namespace('BX.Ozon.SystemAgentList.Vue')

    BX.Ozon.SystemAgentList.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.signedParams = parameters.signedParams
            this.data = parameters.data || {}

            this.initStore()
            this.initComponent()
        },

        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    data: this.data,
                },
                actions: {
                    change(store, payload) {
                        store.commit('changeData', payload)
                    }
                },
                mutations: {
                    changeData(state, params) {
                        if (params.data) {
                            state.data = params.data
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-system-agent',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data,
                            loc: () => BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_'),
                        }),
                template: `
                    <div class='card'>
                        <div class='card-body'>
                            <div v-if='data.LIST.length'>
                                <ozon-agent-list
                                    v-bind:data='data'
                                />
                            </div>
                            <div v-else v-html='loc.DARNEO_OZON_VUE_SYSTEM_LIST_EMPTY'></div>
                        </div>
                    </div>
                `
            })
        },
    }
})()