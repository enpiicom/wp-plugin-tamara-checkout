(function ($) {
    let currentSuccessUrl = new URL(window.location.href);
    let wcOrderId = currentSuccessUrl.searchParams.get('wcOrderId');
    let tamaraAuthoriseInterval;
    function tamaraAuthorise() {
        $.post(
            tamaraCheckoutParams.ajaxUrl, {
                action: 'tamara-authorise',
                wcOrderId: wcOrderId
            }, function (response) {
                if (response.message === 'authorise_success') {
                    // If the order is authorised successfully, stop the ajax call
                    clearInterval(tamaraAuthoriseInterval);
                }
            }
        );
    }
    $(document).ready(function () {
        tamaraAuthorise();

        // The ajax fires every 5s to check if the order is authorised
        // Only do this 3 times
        let x = 0;
        tamaraAuthoriseInterval = window.setInterval(function () {

            tamaraAuthorise();

            if (++x === 3) {
                window.clearInterval(tamaraAuthoriseInterval);
            }
        }, 5000);
    });
})(jQuery);
