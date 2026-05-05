<script>
BX.BitrixVue.component('ozon-agent-list-item', {
    props: {
        item: {
            type: Object,
            required: true
        },
    },
    methods: {
        getAgentActive: function () {
            return this.item.ACTIVE === 'Y'
        },
    },
    template: `
        <tr>
            <td><a :href='item.LINK' target='_blank'>{{ item.NAME }}</a></td>
            <td>
                <span v-if='getAgentActive()' class='badge badge-success'>
                       {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_ACTIVE_Y') }}
                </span>
                <span v-else class='badge badge-danger'>
                    {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_ACTIVE_N') }}
                </span>
            </td>
            <td v-html='item.LAST_EXEC'></td>
            <td v-html='item.NEXT_EXEC'></td>
            <td v-html='item.AGENT_INTERVAL'></td>
        </tr>
    `,
})
</script>