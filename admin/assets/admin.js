(function($) {
    Vue.component('noptinselect2', {
        props: ['value', 'ajax'],
        template: '<select><slot></slot></select>',
        mounted: function() {
            var vm = this
            var data = {}
            console.log(this.ajax)
            if ('0' != this.ajax) {
                data.ajax = {
                    url: noptin.api_url + vm.ajax + '/?per_page=10',
                    data: function(params) {
                        var query = {
                            search: params.term
                        }

                        return query;
                    },
                    processResults: function(data) {

                        var _return = {
                            results: []
                        }
                        data.forEach(function(item, index) {
                            _return.results.push({
                                id: item.id,
                                text: item.title.rendered
                            });
                        })
                        return _return;
                    }
                }
            }

            jQuery(this.$el)
                // init select2
                .select2(data)
                .val(this.value)
                .trigger('change')
                // emit event on change.
                .on('change', function() {
                    vm.$emit('input', this.value)
                })
        },
        watch: {
            value: function(value) {
                // update value
                jQuery(this.$el)
                    .val(value)
                    .trigger('change')
            },
            options: function(options) {
                // update options
                jQuery(this.$el).empty().select2({ data: options })
            }
        },
        destroyed: function() {
            jQuery(this.$el).off().select2('destroy')
        }
    })

    Vue.component('noptincolor', {
        props: ['value'],
        template: '<input type="color" />',
        mounted: function() {
            var vm = this
            jQuery(this.$el)
                .val(this.value)
                // init iris
                .wpColorPicker({
                    change: function(event, ui) {
                        vm.$emit('input', ui.color.toString())
                    },

                    clear: function(event) {
                        vm.$emit('input', '')
                    }
                })
                .val(this.value)
                .trigger('change')
        },
        watch: {
            value: function(value) {
                // update value
                jQuery(this.$el)
                    .val(value)
                    .trigger('change')
            },
        },
    })

})(jQuery);