<?php

namespace Yabafinet\WayEnd\VueComponent;

use Yabafinet\WayEnd\WayEndService;

class CompileVueInstance
{
    public function template(WayEndService $component, $id = 'app')
    {
?>
        <div id="<?=$id?>">
            <?=$component->template()?>
        </div>
        <script>
            _wb_btn_<?=$id?> = Vue.component('wb-btn', {
                props: ['id'],
                data: function () {
                    return {
                        loading: this.$parent.loading,
                        original_text: ''
                    }
                },
                template: '<div v-if="on" v-on:click class="text-danger"><slot></slot></div>',
                methods: {
                    setId: function () {
                        console.log('set-id', this.id);
                        this.$parent.setRequestId(this.id);
                    }
                },
                created: function () {
                    console.log('created:on', this.on);
                }
            });

            let _<?=$id?>_last_values = {};
            var app = new Vue({
                el: '#<?=$id?>',
                components: {
                    _wb_btn_<?=$id?>
                },
                data: <?=$component->propertiesJsObject(['last_values'=> [], 'props_changed'=>[], 'loading' => false])?>,
                methods: {
                    <?=$component->buildMethodsInJs();?>
                    sendUpdate(vm, method = null, args = null) {
                        vm.setLoading(true, method, args);
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
                            vm.setLoading(false, method, args);
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
                    setLoading(isLoading = true, method = null, args = null) {
                        if (isLoading) {
                            this.$emit("loading", { loading: { text: 'Cargando...', icon: '' , requestId : this.requestId}});
                        } else {
                            this.$emit("loading", { loading: { text: null, requestId : this.requestId } });
                        }
                    },
                    setRequestId(requestId) {
                        this.requestId = requestId;
                        console.log('requestId', requestId);
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