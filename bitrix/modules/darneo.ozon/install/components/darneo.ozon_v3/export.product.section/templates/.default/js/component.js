(function () {
    'use strict'

    BX.namespace('BX.Ozon.ExportSection.Vue')

    BX.Ozon.ExportSection.Vue = {
        init: function (parameters) {
            this.ajaxUrl = parameters.ajaxUrl || ''
            this.ajaxImportUrl = parameters.ajaxImportUrl || ''
            this.signedParams = parameters.signedParams
            this.section = parameters.section || {}
            this.tree = parameters.tree || {}

            this.initStore()
            this.initComponent()
        },
        initStore: function () {
            this.store = BX.Vuex.store({
                state: {
                    section: this.section,
                    tree: this.tree,
                    isImportStart: false
                },
                actions: {
                    change(store, payload) {
                        store.commit('changeData', payload)
                    }
                },
                mutations: {
                    changeData(state, params) {
                        if (params.section) {
                            state.section = params.section
                        }
                        if (params.tree) {
                            state.tree = params.tree
                        }
                        if (params.isImportStart !== undefined) {
                            state.isImportStart = params.isImportStart
                        }
                    }
                }
            })
        },

        initComponent: function () {
            BX.BitrixVue.createApp({
                el: '#vue-export-section',
                store: this.store,
                data: BX.delegate(function () {
                    return {
                        signedParams: this.signedParams,
                        request: {
                            isUpdateList: false,
                            isUpdateTree: false,
                            isSaveTree: false
                        },
                        popup: {
                            show: false,
                            title: '',
                            sectionId: 0,
                        },
                        selectedLevel1: this.tree.SELECTED.LEVEL_1,
                        selectedLevel2: this.tree.SELECTED.LEVEL_2,
                        selectedLevel3: '',
                    }
                }, this),
                computed:
                        BX.Vuex.mapState({
                            section: state => state.section,
                            tree: state => state.tree,
                            filter: state => state.filter,
                            isImportStart: state => state.isImportStart,
                        }),
                watch: {
                    section: function () {
                        this.clearRequest()
                    },
                    tree: function () {
                        this.clearRequest()
                    },
                    filter: function () {
                        this.clearRequest()
                    },
                    isImportStart: function (val, old) {
                        if (old && !val) {
                            this.actionReload()
                        }
                    },
                },
                methods: {
                    actionImportStart: function () {
                        this.request.isUpdateTree = true
                        let data = this.getDataAjax()
                        data['action'] = 'import'
                        BX.Ozon.ExportSection.Vue.sendImport(data)
                    },
                    setLevel: function (level1, level2, level3) {
                        this.selectedLevel1 = String(level1)
                        this.selectedLevel2 = String(level2)
                        this.selectedLevel3 = String(level3)
                        this.initFilterUrl({
                            level1: this.selectedLevel1,
                            level2: this.selectedLevel2,
                            level3: this.selectedLevel3,
                        })
                    },
                    clearRequest: function () {
                        this.request.isUpdateTree = false
                        this.request.isUpdateList = false
                        this.request.isSaveTree = false
                    },
                    setPopupData: function (title, sectionId) {
                        this.popup.title = title
                        this.popup.sectionId = Number(sectionId)
                        this.popup.show = true
                    },
                    actionCloseModal: function () {
                        this.popup.title = ''
                        this.popup.sectionId = 0
                        this.popup.show = false
                    },
                    initFilterUrl: function (obj) {
                        const params = new URLSearchParams(obj)
                        let url = params.toString()
                        history.pushState(null, null, '?' + url)
                    },
                    actionReloadTree: function (level1, level2, level3) {
                        this.request.isUpdateTree = true
                        this.setLevel(level1, level2, level3)

                        let data = this.getDataAjax()
                        data['action'] = 'tree'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    actionReload: function () {
                        this.request.isUpdateTree = true
                        let data = this.getDataAjax()
                        data['action'] = 'tree'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    actionSetCategory: function (sectionId, level1, level2, level3) {
                        this.request.isSaveTree = true
                        this.setLevel(level1, level2, level3)

                        let data = this.getDataAjax()
                        data['sectionId'] = sectionId
                        data['action'] = 'setCategory'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    actionDeleteCategory: function (sectionId) {
                        this.request.isUpdateList = true
                        let data = this.getDataAjax()
                        data['sectionId'] = sectionId
                        data['action'] = 'deleteCategory'
                        BX.Ozon.ExportSection.Vue.sendRequest(data)
                    },
                    getDataAjax: function () {
                        let data = {}
                        data['signedParamsString'] = this.signedParams
                        data['level1'] = this.selectedLevel1
                        data['level2'] = this.selectedLevel2
                        data['level3'] = this.selectedLevel3
                        return data
                    },
                },
                template: `
                    <div class='card'>
                        <div class='block_disabled' v-show='request.isUpdateList'></div>
                        <div class='card-body'>
                            <div v-if='section.IBLOCK_ID'>
                                <section-list
                                    v-bind:data='section'
                                    v-on:setPopupData='setPopupData'
                                    v-on:actionDeleteCategory='actionDeleteCategory'
                                />
                            </div>
                            <div v-if='popup.show'>
                                <select-category
                                    v-bind:tree='tree'
                                    v-bind:title='popup.title'
                                    v-bind:sectionId='popup.sectionId'
                                    v-bind:request='request'
                                    v-bind:isImportStart='isImportStart'
                                    v-on:actionReloadTree='actionReloadTree'
                                    v-on:actionSetCategory='actionSetCategory'
                                    v-on:actionCloseModal='actionCloseModal'
                                    v-on:actionImportStart='actionImportStart'
                                />
                            </div>
                        </div>
                    </div>
                `
            })
        },

        sendRequest: function (data) {
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxUrl,
                data: data,
                async: true,
                onsuccess: result => {
                    if (result.STATUS === 'SUCCESS') {
                        if (result.DATA_VUE) {
                            this.store.commit('changeData', {
                                section: result.DATA_VUE.SECTION,
                                tree: result.DATA_VUE.TREE
                            })
                        }
                    }
                    if (result.STATUS === 'ERROR') {
                        $.fancybox.open({
                            src: result.MESSAGE,
                            type: 'html',
                            touch: false,
                            baseClass: 'thanks_msg',
                            openEffect: 'elastic',
                            openMethod: 'zoomIn',
                            closeEffect: 'elastic',
                            closeMethod: 'zoomOut'
                        })
                    }
                },
                onfailure: result => {

                },
            })
        },

        sendImport: function (data) {
            this.store.commit('changeData', { isImportStart: true })
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxImportUrl,
                data: data,
                async: true,
                onsuccess: result => {
                    this.store.commit('changeData', { isImportStart: false })
                },
                onfailure: result => {
                    this.store.commit('changeData', { isImportStart: false })
                },
            })
        },
    }
})()