/**
 * Service Dispatch — Client Dashboard JS
 * Handles client-side interactions
 */
(function ($) {
    'use strict';

    $(function () {
        animateProgressBars();
        initTrackerAnimation();
    });

    function animateProgressBars() {
        $('.sd-progress-fill').each(function () {
            var $bar = $(this);
            var width = $bar.css('width');
            $bar.css('width', 0);
            setTimeout(function () {
                $bar.css('width', width);
            }, 200);
        });
    }

    function initTrackerAnimation() {
        $('.sd-tracker-step').each(function (i) {
            var $step = $(this);
            $step.css({ opacity: 0, transform: 'translateX(-10px)' });
            setTimeout(function () {
                $step.css({
                    opacity: 1,
                    transform: 'translateX(0)',
                    transition: 'all 0.3s ease',
                });
            }, 100 + i * 80);
        });
    }
})(jQuery);
