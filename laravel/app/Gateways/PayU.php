<?php

namespace App\Gateways;

use Illuminate\Support\Facades\App;
use App\Payment;

class PayU implements PaymentGateway
{
    protected $callbackData;

    protected function getMerchantId()
    {
        return env('PAYU_MERCHANT_ID');
    }

    protected function getAccountId()
    {
        return env('PAYU_ACCOUNT_ID');
    }

    protected function getApiKey()
    {
        return env('PAYU_API_KEY');
    }

    protected function getCurrency()
    {
        return 'CLP';
    }

    protected function getSignature(Payment $payment, $reference)
    {
        $apiKey = $this->getApiKey();
        $merchantId = $this->getMerchantId();
        $currency = $this->getCurrency();
        return md5("{$apiKey}~{$merchantId}~{$reference}~{$payment->total}~{$currency}");
    }

    protected function testMode()
    {
        return App::environment('production') ? 0 : 1;
    }

    protected function getGatewayUrl()
    {
        if ($this->testMode()) {
            return 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/';
        }
        return 'https://checkout.payulatam.com/ppp-web-gateway-payu/';
    }

    public function getPaymentRequest(Payment $payment, $data)
    {
        $buyer = $payment->order->buyer;
        return [
            'public_data' => [
                'test' => $this->testMode(),

                'accountId' => $this->getAccountId(),
                'merchantId' => $this->getMerchantId(),
                'referenceCode' => $data['reference'],
                'amount' => $payment->total,
                'currency' => 'CLP',
                'signature' => $this->getSignature($payment, $data['reference']),
                'description' => "Transacción Prilov",

                'confirmationUrl' => route('callback.gateway', ['gateway' => 'pay-u']),

                'buyerFullName' => $buyer->full_name,
                'buyerEmail' => $buyer->email,
                'gateway_url' => $this->getGatewayUrl(),
            ]
        ];
    }

    protected function getPaymentStatus($statePol)
    {
        switch ($statePol) {
            case 4:
                return Payment::SUCCESS;
            default:
                return Payment::ERROR;
        }
    }

    public function validateCallbackData($data)
    {
        return;
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
    }

    public function getStatus()
    {
        return $this->getPaymentStatus($this->callbackData['state_pol']);
    }

    public function getReference()
    {
        return $this->callbackData['reference_sale'];
    }

    public function getData()
    {
        return $this->callbackData;
    }
}
