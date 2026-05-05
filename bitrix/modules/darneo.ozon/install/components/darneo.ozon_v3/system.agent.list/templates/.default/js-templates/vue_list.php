<script>
BX.BitrixVue.component('ozon-agent-list', {
    props: {
        data: {
            type: Object,
            required: true
        },
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
        <div class='table-responsive mt-5'>
            <table class='table table-row-bordered table-striped table-row-gray-200 align-middle gs-7 gy-4'
                   id='basic-1'>
                <thead>
                <tr class='fw-bold text-muted bg-light'>
                    <th class='ps-4 w-250px rounded-start'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_NAME') }}
                    </th>
                    <th class='w-100px'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_ACTIVE') }}
                    </th>
                    <th class='w-125px'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_LAST_EXEC') }}
                    </th>
                    <th class='w-125px'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_NEXT_EXEC') }}
                    </th>
                    <th class='w-125px'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_TABLE_HEAD_INTERVAL') }}
                    </th>
                </tr>
                </thead>
                <tbody>
                <ozon-agent-list-item
                    v-for='item in data.LIST' :key='Number(item.ID)'
                    v-bind:item='item'
                />
                </tbody>
            </table>
        </div>
    `,
})
</script>