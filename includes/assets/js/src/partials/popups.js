// the popup itself is an object that exposes a simple API to open and close vex objects in various ways
let noptinPopup = {

    is_showing: false,

    classes: {
        popup: 'noptin-popup',
        content: 'noptin-popup-content',
        overlay: 'noptin-popup-overlay',
        close: 'noptin-popup-close',
        closing: 'noptin-popup-closing',
        open: 'noptin-popup-open',
        opening: 'noptin-popup-opening',
        opened: 'noptin-popup-opened'
    },

    el: '',

    content: '',

    open(content) {

        // Set state
        this.is_showing = true

        // Create the popup.
        this.el = jQuery('<div></div>').addClass( `${this.classes.popup} ${this.classes.opening}` )

        // overlay.
        this.el.append(`<div class="${this.classes.overlay}"></div>`)

        // Content.
        this.el.append(`<div class="${this.classes.content}"></div>`)
        this.content = this.el.find(`.${this.classes.content}`).html(jQuery(content).prop('outerHTML'))

        // Close when clicking outside the content.
        this.el.on('click', (e) => {

            if ( ! this.content.is( e.target ) && this.content.has( e.target ).length === 0) {
                this.close()
            }

        })

        // Close when clicking on the close button.
        this.el.on('click', `.${this.classes.close}`, () => {
            this.close()
        })

        // Add to DOM
        this.el.appendTo('body')

        // Apply styling to the body
        jQuery('body').addClass( this.classes.open )

        // Remove classes.
        this.el.removeClass( this.classes.opening ).addClass( this.classes.opened )

    },

    replaceContent(content) {

        // Check state
        if ( ! this.is_showing ) {
            return false
        }

        // Replace Content.
        this.content.html(jQuery(content).prop('outerHTML'))

    },

    // Closes the first popup
    close () {

        // Check state.
        if ( ! this.is_showing ) {
            return true
        }

        // Update state
        this.is_showing = false

        // add the closing class.
        this.el.removeClass( this.classes.opened ).addClass( this.classes.closing )

        // Close the popup after animations.
        this.transitionThen(
            this.content,
            () => {

                // Remove the element from dom.
                jQuery(this.el).remove()
    
                // Remove the open class from the body.
                jQuery( 'body' ).removeClass(this.classes.open)
            }
        )

    },

    // Calls the cb after we are done trasitioning
    transitionThen ( el, cb ) {

        // Check if it has a transition/animation.
        let hasTransition = el.css('transition') != 'none' || el.css('-webkit-transition') != 'none'
        let hasAnimation  = ( el.css('animation-name') != 'none' || el.css('-webkit-animation-name') != 'none' )
            && ( el.css('animation-duration') != '0s' || el.css('-webkit-animation-duration') != '0s' )
        let called_cb = false

        let _cb = function() {
            if ( ! called_cb ) {
                cb()
                called_cb = true
            }
        }

        if ( hasAnimation ) {
            el.one('webkitAnimationEnd animationend', _cb)
        } else if ( hasTransition ) {
            el.one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', _cb )
        } else {
            _cb()
        }

        // Cleanup in case the above events do not fire.
		setTimeout(_cb, 300)
    }

}

// Close the popup on esc
jQuery( window ).on( 'keyup', function (e) {
    if (e.keyCode === 27) {
        noptinPopup.close()
    }
})


module.exports = noptinPopup
