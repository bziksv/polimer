<script>
BX.BitrixVue.component('ozon-system-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {
            showSettingLog: false
        }
    },
    computed: {
        loc: function () {
            return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.initTable()
        })
    },
    destroyed: function () {
        $(this.$el).find('#responsive').DataTable().destroy()
    },
    methods: {
        setShowSettingLog: function (value) {
            this.showSettingLog = Boolean(value)
        },
        actionSetSettingLog: function (value) {
            this.$emit('actionSetSettingLog', value)
        },
        initTable: function () {
            $(this.$el).find('#basic-1').DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                info: false,
                paging: false,
                autoWidth: false
            })
        },
    },
    template: `
        <div>
            <div class='d-flex justify-content-between align-items-start flex-wrap mb-2'>
                <div class='d-flex flex-column my-4 text-muted'>
                    <div>
                        <span class='me-1' v-html='loc.DARNEO_OZON_VUE_SYSTEM_LIST_LOG_COUNT_ALL'></span>
                        <span class='badge badge-light text-muted' v-html='data.COUNT_ALL'></span>
                    </div>
                    <div>
                        <span class='me-1' v-html='loc.DARNEO_OZON_VUE_SYSTEM_LIST_LOG_AUTO_CLEAR'></span>
                        <a href='javascript:void(0);'
                           class='btn btn-sm btn-light btn-color-muted btn-active-light-success px-4 py-2 me-4'
                           v-on:click='showSettingLog=!showSettingLog'>
                            <i class='ki-duotone ki-calendar fs-3'>
                                <span class='path1'></span>
                                <span class='path2'></span>
                            </i> {{ data.LOG_SAVE }}</a>
                        <template v-if='showSettingLog'>
                            <ozon-system-modal-log
                                v-bind:count='Number(data.LOG_SAVE)'
                                v-on:setShowSettingLog='setShowSettingLog'
                                v-on:actionSetSettingLog='actionSetSettingLog'
                            />
                        </template>
                    </div>
                </div>
                <div class='d-flex my-4'>
                    <a :href='data.CLEAR' class='btn btn-danger'>
                        <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_CLEAR') }}</span>
                    </a>
                </div>
            </div>
            <div class='table-responsive mt-5'>
                <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4'
                       id='basic-1'>
                    <thead>
                    <tr class='fw-bold text-muted bg-light'>
                        <th class='ps-4 w-200px rounded-start'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_DATE_CREATED') }}
                        </th>
                        <th class='min-w-300px'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_API') }}
                        </th>
                        <th class='min-w-300px'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_URL') }}
                        </th>
                        <th class='w-150px'></th>
                    </tr>
                    </thead>
                    <tbody>
                    <ozon-system-list-item
                        v-for='item in data.LIST' :key='Number(item.ID)'
                        v-bind:item='item'
                    />
                    </tbody>
                </table>
            </div>
        </div>

    `,
})
</script>