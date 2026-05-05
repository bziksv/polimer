//<script>
    (function (window) {
        'use strict'

        const BX = window.BX
        BX.Vue.component('darneo-ozon-popup-vue', {
            props: {
                value: {
                    type: Boolean,
                    required: true,
                },
                title: {
                    type: String,
                    required: true,
                }
            },
            computed: {
                isShow: {
                    get() {
                        return this.value
                    },
                    set(val) {
                        this.$emit('input', val)
                    },
                },
            },
            template: `
                <div v-if='isShow' class='popup-modal modal bd-example-modal-lg show animate'>
                    <div class='modal-dialog'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <h4 class='modal-title' v-html='title'></h4>
                                <button class='btn-close' type='button' @click="$emit('input', false)"></button>
                            </div>
                            <slot></slot>
                        </div>
                    </div>
                </div>
            `
        })
    })(window)
    //</script>