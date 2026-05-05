<script>
BX.BitrixVue.component('ozon-system-detail', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {}
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initJson('#sendJson', JSON.parse(this.data.DATA_SEND))
            this.initJson('#answer', JSON.parse(this.data.DATA_RECEIVED))
        })
    },
    methods: {
        initJson: function (selector, data) {
            new JsonEditor($(this.$el).find(selector), data)
        },
        saveTxt: function () {
            BX.Ozon.SystemLogDetail.Vue.saveTxt()
        }
    },
    template: `
        <div>
            <div class='card-header card-header-stretch'>
                <h3 class='card-title'>
                    <span class='badge badge-primary me-2'>#: {{ data.ID }}</span>
                    <span v-if='data.DOCS && data.DOCS.TITLE.length' v-html='data.DOCS.TITLE'></span>
                    <span v-else v-html='data.URL'></span>
                </h3>
                <div class='card-toolbar'>
                    <ul class='nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0'>
                        <li class='nav-item'>
                            <a class='nav-link active' data-bs-toggle='tab' href='#tab_info'
                               v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_TAB_INFO'></a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' data-bs-toggle='tab' href='#tab_send'
                               v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_TAB_SEND'></a>
                        </li>
                        <li class='nav-item'>
                            <a class='nav-link' data-bs-toggle='tab' href='#tab_received'
                               v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_TAB_RECEIVED'></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class='card-body'>
                <div class='tab-content' id='myTabContent'>
                    <div class='tab-pane fade show active' id='tab_info' role='tabpanel'>
                        <div class='mb-10' v-if='data.DOCS && data.DOCS.URL.length'>
                            <label class='form-label' v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_TAB_INFO'></label>
                            <div class='position-relative'>
                                <a v-bind:href='data.DOCS.URL' target='_blank' v-html='data.URL'></a>
                            </div>
                        </div>
                        <div class='mb-10'>
                            <label class='form-label'
                                   v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_DATA_DATE_CREATE'></label>
                            <div class='position-relative'>
                                {{ data.DATE_CREATED }}
                            </div>
                        </div>
                        <div class='mb-10'>
                            <label class='form-label' v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_DATA_CLIENT_ID'></label>
                            <div class='position-relative'>
                                {{ data.CLIENT_ID }}
                            </div>
                        </div>
                        <div class='mb-10'>
                            <label class='form-label' v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_DATA_KEY'></label>
                            <div class='position-relative'>
                                {{ data.KEY }}
                            </div>
                        </div>
                        <div class='mb-10'>
                            <label class='form-label'
                                   v-html='loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_DATA_METHOD_TRACKER'></label>
                            <div class='position-relative' v-html='data.METHOD_TRACKER'></div>
                        </div>
                        <div class='mb-10'>
                            <div class='position-relative'>
                                <a href='javascript:void(0)' class='btn btn-sm btn-success' v-on:click='saveTxt()'>
                                    <i class='ki-duotone ki-file-down fs-1'>
                                        <span class='path1'></span>
                                        <span class='path2'></span>
                                    </i>
                                    {{ loc.DARNEO_OZON_VUE_SYSTEM_DETAIL_DOWNLOAD }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class='tab-pane fade' id='tab_send' role='tabpanel'>
                        <div id='sendJson'></div>
                    </div>
                    <div class='tab-pane fade' id='tab_received' role='tabpanel'>
                        <div id='answer'></div>
                    </div>
                </div>
            </div>
        </div>
    `,
})
</script>
