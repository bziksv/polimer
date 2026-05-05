<script>
    BX.BitrixVue.component('select-category', {
        props: {
            tree: {
                type: Object,
                required: false
            },
            title: {
                type: String,
                required: false
            },
            sectionId: {
                type: Number,
                required: false
            },
            request: {
                type: Object,
                required: false
            },
            isImportStart: {
                type: Boolean,
                required: false
            },
        },
        data: function () {
            return {
                popupModal: true,
                selectedDefault: '',
                selectedLevel1: String(this.tree.SELECTED.LEVEL_1),
                selectedLevel2: String(this.tree.SELECTED.LEVEL_2),
                selectedLevel3: '',
                isSend: false,
                isSave: false,
            }
        },
        computed: {
            loc: function () {
                return BX.BitrixVue.getFilteredPhrases('DARNEO_OZON_')
            },
        },
        watch: {
            popupModal: function (val) {
                if (!val) {
                    this.$emit('actionCloseModal')
                }
            },
            tree: function () {
                this.isSend = false
                if (this.isSave === true) {
                    this.isSave = false
                    this.$emit('actionCloseModal')
                }
            },
            selectedLevel1: function () {
                if (this.isSend === false) {
                    this.isSend = true
                    this.selectedLevel2 = this.selectedDefault
                    this.selectedLevel3 = this.selectedDefault
                    this.setLevel()
                }
            },
            selectedLevel2: function () {
                if (this.isSend === false) {
                    this.isSend = true
                    this.selectedLevel3 = this.selectedDefault
                    this.setLevel()
                }
            },
            selectedLevel3: function () {
                if (this.isSend === false) {
                    this.isSend = true
                    this.setLevel()
                }
            }
        },
        methods: {
            setLevel: function () {
                this.$emit('actionReloadTree', this.selectedLevel1, this.selectedLevel2, this.selectedLevel3)
            },
            getCategoryValues: function (dataLevel) {
                let arr = []
                for (let key in dataLevel) {
                    let item = dataLevel[key]
                    let row = {}
                    row['id'] = item.CATEGORY_ID
                    row['text'] = item.CATEGORY_NAME
                    row['selected'] = item.ACTIVE
                    arr.push(row)
                }
                return arr
            },
            getTypeValues: function (dataLevel) {
                let arr = []
                for (let key in dataLevel) {
                    let item = dataLevel[key]
                    let row = {}
                    row['id'] = item.TYPE_ID
                    row['text'] = item.TYPE_NAME
                    row['selected'] = item.ACTIVE
                    arr.push(row)
                }
                return arr
            },
            actionSetCategory: function () {
                if (this.selectedLevel3 !== this.selectedDefault) {
                    this.isSave = true
                    this.$emit('actionSetCategory', this.sectionId, this.selectedLevel1, this.selectedLevel2, this.selectedLevel3)
                }
            },
            isDisableButton: function () {
                return this.selectedLevel3 === this.selectedDefault || this.request.isSaveTree
            },
            actionImportStart: function () {
                this.$emit('actionImportStart')
            },
        },
        template: `
            <div>
                <darneo-ozon-popup-vue
                    v-model='popupModal'
                    v-bind:title='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_MODAL_TITLE'>
                    <div class='modal-content block_disabled' v-show='request.isUpdateTree'></div>
                    <div class='modal-body'>
                        <label class='form-label' v-html='title'></label>
                        <div class='input-group mb-5'>
                            <div class='w-100 d-flex'>
                                <darneo-ozon-select
                                    v-bind:options='getCategoryValues(tree.LEVEL_1)'
                                    v-bind:value='selectedLevel1'
                                    v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_PLACEHOLDER_CATEGORY_L1'
                                    v-on:input='selectedLevel1 = $event'
                                    class='w-100'
                                />
                            </div>
                        </div>
                        <div class='input-group mb-5'>
                            <div class='w-100 d-flex'>
                                <darneo-ozon-select
                                    v-bind:options='getCategoryValues(tree.LEVEL_2)'
                                    v-bind:value='selectedLevel2'
                                    v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_PLACEHOLDER_CATEGORY_L2'
                                    v-on:input='selectedLevel2 = $event'
                                    class='w-100'
                                />
                                <a class='m-2' href='javascript:void(0)'
                                   v-on:click='selectedLevel2=selectedDefault'
                                   v-show='selectedLevel2 !== selectedDefault'>
                                    <i class='ki-duotone ki-cross-square fs-2x'>
                                        <i class='path1'></i>
                                        <i class='path2'></i>
                                    </i>
                                </a>
                            </div>
                        </div>
                        <div class='input-group mb-5'>
                            <div class='w-100 d-flex'>
                                <darneo-ozon-select
                                    v-bind:options='getTypeValues(tree.LEVEL_3)'
                                    v-bind:value='selectedLevel3'
                                    v-bind:placeholder='loc.DARNEO_OZON_VUE_PRODUCT_SECTION_PLACEHOLDER_CATEGORY_L3'
                                    v-on:input='selectedLevel3 = $event'
                                    class='w-100'
                                />
                                <a class='m-2' href='javascript:void(0)'
                                   v-on:click='selectedLevel3=selectedDefault'
                                   v-show='selectedLevel3 !== selectedDefault'>
                                    <i class='ki-duotone ki-cross-square fs-2x'>
                                        <i class='path1'></i>
                                        <i class='path2'></i>
                                    </i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer'>

                        <section-list-category-import
                            v-bind:isImportStart='isImportStart'
                            v-on:actionImportStart='actionImportStart'
                        />

                        <button class='btn btn-primary p-relative' type='button' v-bind:disabled='isDisableButton()'
                                v-on:click='actionSetCategory()'>
                            <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_SECTION_BUTTON_SAVE') }}</span>
                            <i class='fa fa-spin fa-spinner' v-show='request.isSaveTree'></i>
                        </button>
                    </div>

                </darneo-ozon-popup-vue>
            </div>
        `,
    })
</script>