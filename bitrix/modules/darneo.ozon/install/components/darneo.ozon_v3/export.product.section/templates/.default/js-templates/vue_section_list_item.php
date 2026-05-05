<script>
    BX.BitrixVue.component('section-list-item', {
        props: {
            item: {
                type: Object,
                required: true
            },
            isFirstOpen: {
                type: Boolean,
                required: false
            },
        },
        data: function () {
            return {
                isOpen: this.isFirstOpen
            }
        },
        computed: {
            isFolder: function () {
                return this.item.CHILD && this.item.CHILD.length
            }
        },
        methods: {
            toggle: function () {
                if (this.isFolder) {
                    this.isOpen = !this.isOpen
                }
            },
            makeFolder: function () {
                if (!this.isFolder) {
                    this.$emit('make-folder', this.item)
                    this.isOpen = true
                }
            },
            getClass: function (isOpen) {
                return isOpen ? 'fa fa-angle-down' : 'fa fa-angle-right'
            },
            setPopupData: function (title, sectionId) {
                this.$emit('setPopupData', title, sectionId)
            },
            actionDeleteCategory: function (sectionId) {
                this.$emit('actionDeleteCategory', sectionId)
            },
        },
        template: `
            <li class='pl-navs-inline m-5'>
                <div class='d-flex align-items-center'>
                    <button class='btn btn-link btn-sm text-muted active' :class='{bold: isFolder}' @click='toggle'
                            @dblclick='makeFolder'>
                        <i v-bind:class='getClass(isOpen)' v-if='isFolder'></i>
                        <i v-else></i>
                        <span> {{ item.NAME }}</span>
                    </button>
                    <button class='btn btn-primary btn-sm link-section'
                            v-show='item.CATEGORY.length > 0'>
                        {{ item.CATEGORY }}
                    </button>
                    <a href='javascript:void(0)' class='ms-5 d-flex align-items-center'
                       v-if='item.CATEGORY.length > 0'
                       v-on:click='actionDeleteCategory(item.ID)'>
                        <i class='ki-duotone ki-trash-square fs-2x'>
                            <i class='path1'></i>
                            <i class='path2'></i>
                            <i class='path3'></i>
                            <i class='path4'></i>
                        </i>
                    </a>
                    <button class='btn btn-warning btn-sm text-dark link-section'
                            v-show='!item.CATEGORY.length'
                            v-on:click='setPopupData(item.NAME, item.ID)'>
                        {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_PRODUCT_SECTION_BUTTON_SELECT') }}
                    </button>
                </div>
                <ul v-show='isOpen' v-if='isFolder'>
                    <section-list-item
                        class='item'
                        v-for='child in item.CHILD'
                        :key='Number(child.ID)'
                        :item='child'
                        @make-folder='$emit("make-folder", $event)'
                        @add-item='$emit("add-item", $event)'
                        v-on:setPopupData='setPopupData'
                        v-on:actionDeleteCategory='actionDeleteCategory'
                    />
                </ul>
            </li>
        `,
    })
</script>