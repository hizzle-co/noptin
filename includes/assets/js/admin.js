(function($) {

    Vue.component('noptinselect2', {
        props: ['value', 'ajax'],
        template: '<select><slot></slot></select>',
        mounted: function() {
            var vmSelect = this
            var data = {}

            if ('0' != this.ajax) {
                data.ajax = {
                    url: noptin.api_url + vmSelect.ajax + '/?per_page=10',
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
                    vmSelect.$emit('input', this.value)
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

    var editorInstances = {}
    Vue.component('noptineditor', {
        props: ['value', 'id'],
        template: '<textarea><slot></slot></textarea>',
        mounted: function() {
            var vmEditor = this
            var el = jQuery(this.$el)
            var editor = wp.codeEditor.initialize( el )
            editor.codemirror.on('change', function( cm, change ){
                vmEditor.$emit('input', cm.getValue())
            })
            editorInstances[this.id] = editor
            editorInstances[this.id].codemirror.getDoc().setValue(this.value);
        },
        watch: {
            value: function(value) {
                if( editorInstances[this.id] ) {
                    //editorInstances[this.id].codemirror.getDoc().setValue(value);
                }
            },
        }
    })

    Vue.component('noptincolor', {
        props: ['value'],
        template: '<input type="color" />',
        mounted: function() {
            var vmColor = this
            jQuery(this.$el)
                .val(this.value)
                // init iris
                .wpColorPicker({
                    change: function(event, ui) {
                        vmColor.$emit('input', ui.color.toString())
                    },

                    clear: function(event) {
                        vmColor.$emit('input', '')
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
            togglePanel: function(id) {
                
                var noptinPanel  = $("#noptinPanel" + id).find('.noptin-popup-editor-panel-body')

                var panelHeight = 0;

                if(!this[id]) {
                    var previousCss  = $(noptinPanel).attr("style");

                    $(noptinPanel).css({
                        position:   'absolute',
                        visibility: 'hidden',
                        display:    'block',
                        height: 'auto'
                    });

                    var panelHeight = $(noptinPanel).height();

                    $(noptinPanel).attr("style", previousCss ? previousCss : "");
                }

                var that = this
                $( noptinPanel ).animate({
                    height: panelHeight,
                }, 600, function(){
                    that[id] = !that[id]
                    if( that[id] ){
                        $(noptinPanel).css({
                            height: 'auto'
                        });
                    }        
                });
                
            },
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
            saveAsTemplate: function() {
                var saveText  = this.saveAsTemplateText
                this.saveAsTemplateText = this.savingTemplateText;
                var that = this

                jQuery.post(noptinEditor.ajaxurl, {
                    _ajax_nonce: noptinEditor.nonce,
                    action: "noptin_save_optin_form_as_template",
                    state: vm.$data
                })
                .done( function(){
                    that.showSuccess( that.savingTemplateSuccess )
                    that.saveAsTemplateText = saveText
                })
                .fail( function(){
                    that.showError( that.savingTemplateError )
                    that.saveAsTemplateText = saveText
                })

            },
            updateCustomCss: function() {
                jQuery('#popupCustomCSS').text(this.CSS)
            },
            upload_image: function( key ) {
                var image = wp.media({ 
                    title: 'Upload Image',
                    multiple: false
                })
                .open()
                .on('select', function(e){
                    var uploaded_image = image.state().get('selection').first();
                    vm[key] = uploaded_image.toJSON().sizes.thumbnail.url;
                })
            },
            changeFormType: function() {

                //Sidebar
                if( this.optinType == 'sidebar' ) {
                    this.formHeight = '400px'
                    this.formWidth  = '260px'
                    this.singleLine = false
                    return
                }

                this.formHeight = '250px'
                this.formWidth  = '520px'

            },
            changeColorTheme: function() {
                var colors = this.colorTheme.split(" ")
                this.noptinFormBg = colors[0]
                this.noptinFormBorderColor = colors[2]
                this.noptinButtonColor = colors[0]
                this.noptinButtonBg  = colors[1]
                this.titleColor  = colors[1]
                this.descriptionColor  = colors[1]
                this.noteColor  = colors[1]
            },
            changeTemplate: function() {
                var templates = JSON.parse( noptinEditor.templates ),
                template = this.Template

                if( templates[template] ) {
                    Object.keys( templates[template] ).forEach( function( key ) {
                        vm[key] = templates[template][key]
                    })
                }
            },
            showSuccess: function( msg ) {
                this.hasSuccess = true;
                this.Success    = msg;

                setTimeout( function(){
                    vm.hasSuccess = false;
                    vm.Success    = '';
                }, 5000)
            },
            showError: function( msg ) {
                this.hasError = true;
                this.Error    = msg;

                setTimeout( function(){
                    vm.hasError = false;
                    vm.Error    = '';
                }, 5000)
            },
            save: function() {
                var saveText  = this.saveText
                this.saveText = this.savingText;
                var that = this

                jQuery.post(noptinEditor.ajaxurl, {
                    _ajax_nonce: noptinEditor.nonce,
                    action: "noptin_save_optin_form",
                    state: vm.$data,
                    html: jQuery('.noptin-popup-wrapper').html()
                })
                .done( function(){
                    that.showSuccess( that.savingSuccess )
                    that.saveText = saveText
                })
                .fail( function(){
                    that.showError( that.savingError )
                    that.saveText = saveText
                })

            }
        },
        mounted: function() {
            jQuery('#popupCustomCSS').text(this.CSS)
            jQuery('.noptin-popup-designer-loader').hide()
        },
    })

})(jQuery);