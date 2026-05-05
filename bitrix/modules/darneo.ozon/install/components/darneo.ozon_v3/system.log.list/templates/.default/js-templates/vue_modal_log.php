<script>
BX.BitrixVue.component('ozon-system-modal-log', {
    props: {
        count: {
            type: Number,
            required: true
        },
    },
    data: function () {
        return {
            logCount: this.count,
            isSendSave: false,
        }
    },
    watch: {
        count: function () {
            this.initClose()
        },
    },
    mounted: function () {
        this.$nextTick(function () {
            this.init()
        })
    },
    methods: {
        init: function () {
            let vm = this
            $(this.$el).modal('toggle')
            $(this.$el).on('hidden.bs.modal', function () {
                vm.$emit('setShowSettingLog', false)
            })
        },
        initClose: function () {
            $(this.$el).modal('toggle')
            this.$emit('setShowSettingLog', false)
        },
        actionSet: function () {
            if (Number(this.logCount) !== Number(this.count)) {
                this.isSendSave = true
                this.$emit('actionSetSettingLog', this.logCount)
            } else {
                this.initClose()
            }
        },
    },
    template: `
        <div class='modal fade'>
            <div class='modal-dialog'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_LOG_SETTINGS') }}
                        </h5>
                        <button class='btn-close' type='button' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>
                        <label class='form-label'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_LOG_AUTO_CLEAR') }}
                        </label>
                        <input type='number' min='1' max='365' class='form-control' v-model='logCount'>
                        <div class='col-md-12 mt-2'>
                            <button type='button' class='btn btn-primary btn-xs fs-8' v-on:click='actionSet()'
                                    v-bind:disabled='isSendSave'>
                                <span>{{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_LOG_SAVE') }}</span>
                                <i class='fa fa-spin fa-spinner' v-show='isSendSave'></i>
                            </button>
                        </div>
                    </div>
                    <div class='modal-footer'>
                        <button class='btn btn-secondary' type='button' data-bs-dismiss='modal'>
                            {{ $Bitrix.Loc.getMessage('DARNEO_OZON_VUE_SYSTEM_LIST_CLOSE') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `,
})
</script>