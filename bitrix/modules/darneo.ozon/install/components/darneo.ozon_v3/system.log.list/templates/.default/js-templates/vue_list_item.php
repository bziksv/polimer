<script>
BX.BitrixVue.component('ozon-system-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    data: function () {
        return {}
    },
    mounted: function () {
        this.$nextTick(function () {
            BX.UI.Hint.init(BX('basic-1'))
        })
    },
    updated: function () {
        this.$nextTick(function () {
            BX.UI.Hint.init(BX('basic-1'))
        })
    },
    template: `
        <tr>
            <td>
                <span>ID: {{ item.ID }}</span><br>
                <span v-html='item.DATE_CREATED'></span>
            </td>
            <td>
                <span>CLIENT_ID: {{ item.CLIENT_ID }}</span><br>
                <span v-html='item.KEY'></span>
            </td>
            <td>
                <div v-if='item.DOCS && item.DOCS.URL.length'>
                    <span v-html='item.DOCS.TITLE'></span><br>
                    <a v-bind:href='item.DOCS.URL'
                       target='_blank' v-html='item.URL'></a>
                </div>
                <div v-else>
                    <span v-html='item.URL'></span>
                </div>
            </td>
            <td>
                <a :href='item.DETAIL_PAGE_URL' class='btn btn-primary'>
                    <span>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_SHOW') }}
                    </span>
                </a>
            </td>
        </tr>
    `,
})
</script>