(function($) {

    Vue.component('noptinselect2', {
        props: ['value', 'ajax'],
        template: '<select><slot></slot></select>',
        mounted: function() {
            var vm = this
            var data = {}

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

    //List filter
    $(document).ready(function() {
        $(".noptin-list-filter input").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $('.noptin-list-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });

    var vm = new Vue({
        el: '#noptin-popups-app',
        data: noptinEditor.data,
        computed: {
            showingFullName: function() {
                return this.showNameField && !this.firstLastName
            },
            showingSingleName: function() {
                return this.showNameField && this.firstLastName
            }
        },
        methods: {
            previewPopup: function() {
                this.isPreviewShowing = true
                var _html = jQuery('.noptin-popup-wrapper').html()
                jQuery("#noptin-popup-preview")
                    .html(_html)
                    .addClass('noptin-preview-showing')
                    .find('.noptin-popup-close')
                    .show()
                    .on('click', function() {
                        vm.closePopup()
                    })

                //Hide popup when user clicks outside
                jQuery("#noptin-popup-preview")
                    .off('noptin-popup')
                    .on('click', function(e) {
                        var container = jQuery(this).find(".noptin-popup-form-wrapper");

                        // if the target of the click isn't the container nor a descendant of the container
                        if (!container.is(e.target) && container.has(e.target).length === 0) {
                            vm.closePopup()
                        }
                    });
            },
            closePopup: function() {
                this.isPreviewShowing = false
                jQuery("#noptin-popup-preview").removeClass('noptin-preview-showing').html('')
            },
            updateCustomCss: function() {
                jQuery('#popupCustomCSS').text(this.custom_css)
            },
            save: function() {
                this.saveText = 'Saving...';

                jQuery.post(noptinEditor.ajaxurl, {
                    nonce: noptinEditor.nonce,
                    action: "noptin_save_popup",
                    state: vm.$data,
                })

            }
        },
        mounted: function() {
            jQuery('#popupCustomCSS').text(this.custom_css)
        },
    })

})(jQuery);