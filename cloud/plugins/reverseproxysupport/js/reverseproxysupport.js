(function() {
    $(function() {
        $form = $('#reverse-proxy-settings-form');
        $testButton = $('#reverse-proxy-test-config');
        $saveButton = $('#reverse-proxy-save-config');
        $proxyURLInput = $('#reverse-proxy-url');

        $form
            .change(function() {
                if ($proxyURLInput.val() !== '') {
                    $saveButton.prop('disabled', true);
                } else {
                    $saveButton.prop('disabled', false);
                }
            })
            .submit(function() {
                if ($saveButton.is(':disabled')) {
                    return false;
                }
            });

        $currentRequest = null;
        var errorMessage = gdn.definition('ErrorReverseProxyTest', 'The test configuration failed.');
        $testButton.click(function() {
            if ($currentRequest !== null) {
                $currentRequest.abort('user_cancel');
                $currentRequest = null;
            }

            var proxyURL = $proxyURLInput.val();
            if (!proxyURL.length) {
                return;
            }
            var formattedProxyURL = proxyURL.substr(-1) === '/' ? proxyURL.substr(0, proxyURL.length - 1) : proxyURL;
            var schemePaddedProxyUrl = formattedProxyURL;
            if (schemePaddedProxyUrl.substr(0, 2) === '//') {
                schemePaddedProxyUrl = window.location.protocol + formattedProxyURL;
            }

            $currentRequest =
                $.ajax({
                    dataType: 'jsonp',
                    url: schemePaddedProxyUrl+$form.data('proxyValidatePath'),
                    data: {
                        "expectedProxyFor": formattedProxyURL,
                        "validationID": $form.data('validationId')
                    },
                    timeout: 2000
                })
                .always(function(data, status) {
                    if (status === 'user_cancel') {
                        return;
                    }

                    var success = (status === 'success' && data['Valid'] === true);

                    if (success) {
                        $saveButton.prop('disabled', false);

                        var msg = gdn.definition(
                            'SuccessReverseProxyTest',
                            'Test configuration success. You can save now!'
                        );
                        gdn.informMessage(msg);

                    } else if (status === 'success') {
                        gdn.informError(errorMessage+"<br>"+data['ErrorMessages'].join(' '));
                    } else {
                        gdn.informError(errorMessage+"<br>"+gdn.definition('ErrorURLReverseProxyTest', 'Make sure that Reverse Proxy URL is correct.'));
                    }

                    $currentRequest = null;
                })
            ;
        })


        $('#js-foggy-redirect').change(function() {
            var $target = $('.js-foggy-redirect', '#reverse-proxy-settings-form');

            var callback;
            if ($(this).is(':checked')) {
                callback = function() {
                    var self = $(this);
                    if (self.hasClass('foggy')) {
                        self.removeClass('foggy');
                    }
                };
            } else {
                callback = function() {
                    var self = $(this);
                    if (!self.hasClass('foggy')) {
                        self.addClass('foggy');
                    }
                };
            }

            $target.each(callback);
        });
    });
}());
