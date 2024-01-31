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

            let _<?=$id?>_last_values = {};
            var app = new Vue({
                el: '#<?=$id?>',
                components: {},
                data: <?=$component->propertiesJsObject(['last_values'=> [], 'props_changed'=>[], 'loading' => false, 'request_id' => null])?>,
                methods: {
                    <?=$component->buildMethodsInJs();?>

                    sendUpdate(vm, method = null, args = null) {
                        console.log('sendUpdate', method, args, this.request_id);

                        let _requestId = this.request_id;
                        let last_html = document.getElementById(_requestId)?.innerHTML;

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
                            vm.setLoading(false, _requestId, last_html);
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

                    setLoading(isLoading = true, requestId = null, last_html = null) {
                        const suspense_el = document.getElementById(requestId);
                        if (!requestId) {
                            return;
                        }
                        if (isLoading) {
                            suspense_el.style.display = 'none';
                            console.log('[requestId] loading: '+requestId);
                            const suspense_load = document.createElement("div");
                            suspense_load.id = requestId+'-loading';
                            suspense_load.className = 'loading';
                            suspense_load.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                            suspense_el.after(suspense_load);
                            //this.$emit("loading", { loading: { text: 'Cargando...', icon: '' , requestId : requestId}});
                        } else {
                            //this.$emit("loading", { loading: { text: null, requestId : requestId } });
                            console.log('[requestId] loaded: '+requestId);
                            suspense_el.style.display = 'block';
                            const suspense_load = document.getElementById(requestId+'-loading');
                            suspense_load.remove();
                            //suspense_el.innerHTML = last_html;
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
                mounted: function () {
                    this.sendUpdate(this, 'mounted');
                },
                updated: function () {}
            });
        </script>
<?php
    }
}