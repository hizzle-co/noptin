/**
 * Fade out an element
 *
 * @param {HTMLElement} element
 */
export default function fadeOut(element) {
    var opacity = 1;
    var timer = setInterval(function () {
        if (opacity <= 0.1) {
            clearInterval(timer);
            element.style.display = 'none';
        }

        element.style.opacity = opacity;
        opacity -= opacity * 0.1;
    }, 10);
}
