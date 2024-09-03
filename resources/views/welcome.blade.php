<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Pay Integration</title>
    <script src="https://pay.google.com/gp/p/js/pay.js" async></script>
</head>
<body>
    <div id="google-pay-button-container"></div>

    <script>
        function onGooglePayLoaded() {
            const paymentsClient = new google.payments.api.PaymentsClient({environment: 'TEST'}); // 'PRODUCTION' for live
            const paymentDataRequest = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [{
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                        allowedCardNetworks: ['VISA', 'MASTERCARD']
                    },
                    tokenizationSpecification: {
                        type: 'PAYMENT_GATEWAY',
                        parameters: {
                            gateway: 'square',
                            gatewayMerchantId: 'sandbox-sq0idb-C2y_5H0V9Gr6PJ2VyptKeg' // Square Application ID
                        }
                    }
                }],
                merchantInfo: {
                    merchantName: 'Sourabh'
                },
                transactionInfo: {
                    totalPriceStatus: 'FINAL',
                    totalPrice: '1.00',
                    currencyCode: 'USD'
                }
            };

            const button = paymentsClient.createButton({
                onClick: function() {
                    paymentsClient.loadPaymentData(paymentDataRequest)
                        .then(handlePaymentSuccess)
                        .catch(handlePaymentError);
                }
            });

            document.getElementById('google-pay-button-container').appendChild(button);
        }

        function handlePaymentSuccess(paymentData) {
            const googlePayToken = paymentData.paymentMethodData.tokenizationData.token;

            fetch('/process-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ googlePayToken: googlePayToken })
            })
            .then(response => response.json())
            .then(data => console.log('Payment processed:', data))
            .catch(error => console.error('Error processing payment:', error));
        }

        function handlePaymentError(error) {
            console.error('Payment failed:', error);
        }

        // Initialize Google Pay button after script loads
        window.onload = function() {
            if (window.google && window.google.payments) {
                onGooglePayLoaded();
            } else {
                console.error('Google Pay script not loaded');
            }
        };
    </script>
</body>
</html>
