<script>
    BX.BitrixVue.component('section-list-category-import', {
        props: {
            isImportStart: {
                type: Boolean,
                required: false
            },
        },
        methods: {
            actionStart: function () {
                if (!this.isImportStart) {
                    this.$emit('actionImportStart')
                }
            },
        },
        template: `
            <a href='javascript:void(0)' class='btn btn-secondary me-5'
               v-on:click='actionStart()' v-bind:class='{disabled:isImportStart}'>
                {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_CATEGORY_BUTTON_LOAD') }}
                <i class='fa fa-spin fa-spinner' v-show='isImportStart'></i>
            </a>

        `,
    })
</script>