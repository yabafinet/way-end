<?php

namespace Yabafinet\WayEnd\Vue;

use Yabafinet\WayEnd\WayEndService;

class CompileVueInstance
{
    public function template(WayEndService $component, $id = 'app')
    {
        $component->reflectionClass();
?>
        <div id="<?=$id?>">
            <?php _wn_template()?>
        </div>
        <script>
            _wb_btn_<?=$id?> = Vue.component('wn-suspense', {
                props: [],
                data: function () {
                    return {
                        loading: false,
                        id: this.defaultId()
                    }
                },
                template:
                    '<div v-if="!loading" v-on:click="setId" class="text-danger"><slot></slot></div>'+
                    '<div v-else class="text-danger"><i class="fa fa-spinner"></i> ... </div>',
                methods: {
                    setId: function () {
                        console.log('setId', this.id)
                        this.$parent.setRequestId(this.id);
                    },
                    defaultId: function () {
                        return Math.floor(Math.random() * 8);
                    }
                },
                mounted: function () {
                    if (this.id === undefined) {
                        this.id = this.defaultId();
                    }
                },
                created: function () {
                    this.$parent.$on('loading', (data) => {
                        if (data.loading.requestId !== this.id) {
                            if (data.loading.text === null) {
                                this.loading = false;
                            } else {
                                this.loading = true;
                            }
                            console.log('loading', data.loading, 'LocalId', this.id);
                        }
                    });
                }
            });

            let _<?=$id?>_last_values = {};
            var app = new Vue({
                el: '#<?=$id?>',
                components: {
                    'wn-suspense': _wb_btn_<?=$id?>,
                },
                data: <?=$component->propertiesJsObject(['last_values'=> [], 'props_changed'=>[], 'loading' => false])?>,
                methods: {
                    <?=$component->buildMethodsInJs();?>
                    sendUpdate(vm, method = null, args = null) {
                        console.log('sendUpdate', method, args);
                        let _requestId = this.requestId;
                        vm.setLoading(true, _requestId);
                        let dataToSend = this.preparePropSendToServer(vm);
                        vm.$http.post('<?=$component->getCurrentUrl(['act'=>'update'])?>', {
                            method: method, changed: dataToSend, args: args
                        }).then( response => {
                            response.body.data.forEach(function (val) {
                                vm[val.name] = val.value;
                                if (_<?=$id?>_last_values[val.name] !== val.value) {
                                    _<?=$id?>_last_values[val.name] = val.value;
                                }
                            });
                            vm.setLoading(false, _requestId);
                        }, response => {
                            console.warn(response);
                        });
                    },
                    lastPropertiesValues(vm) {
                        _<?=$id?>_last_values = {};
                        Object.entries(vm.$data).forEach(function (entry) {
                            const [name, value] = entry;
                            _<?=$id?>_last_values[name] = value;
                        });
                    },
                    preparePropSendToServer(vm) {
                        let data = {};
                        Object.entries(vm.$data).forEach(function (entry) {
                            const [name, value] = entry;
                            if ((JSON.stringify(_<?=$id?>_last_values[name]) !== JSON.stringify(value)) || vm.props_changed[name]) {
                                data[name] = value;
                                vm.props_changed[name] = value;
                            }
                        });
                        return data;
                    },
                    setLoading(isLoading = true, requestId = null) {
                        if (isLoading) {
                            console.log('[requestId] loading: '+requestId);
                            this.$emit("loading", { loading: { text: 'Cargando...', icon: '' , requestId : requestId}});
                        } else {
                            this.$emit("loading", { loading: { text: null, requestId : requestId } });
                            console.log('[requestId] loaded: '+requestId);
                        }
                    },
                    setRequestId(requestId) {
                        this.requestId = requestId;
                        console.log('setRequestId', requestId);
                    },
                },
                created: function () {
                    this.lastPropertiesValues(this);
                },
                updated: function () {}
            });
        </script>
<?php
    }
}