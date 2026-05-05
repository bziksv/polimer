(function () {
    'use strict'

    BX.namespace('BX.Ozon.SystemLogDetail.Vue')

    BX.Ozon.SystemLogDetail.Vue = {
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
                    data: this.data
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
                el: '#vue-system-log',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false
                        },
                        filter: ''
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            data: state => state.data,
                            loc: () => BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_'),
                        }),
                watch: {
                    data: function () {
                        this.request.isUpdateList = false
                    },
                },
                methods: {},
                template: `
                    <div class='card'>
                        <ozon-system-detail
                            v-bind:data='data.DATA'
                        />
                    </div>
                `
            })
        },

        saveTxt: function () {
            this.data.DATA.DATA_SEND = JSON.parse(this.data.DATA.DATA_SEND)
            this.data.DATA.DATA_RECEIVED = JSON.parse(this.data.DATA.DATA_RECEIVED)

            const dataStr = JSON.stringify(this.data.DATA, null, 2)
            const blob = new Blob([dataStr], { type: 'application/json' })
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.style.display = 'none'
            a.href = url
            a.download = this.data.DATA.ID + '.json'
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
        }
    }
})()
