<?php

namespace App\Services;

class PayUService
{
    protected $merchantId;
    protected $apiKey;
    protected $accountId;
    protected $test;
    protected $url;

    public function __construct()
    {
        $this->merchantId = config('services.payu.merchant_id', '508029'); // Sandbox defaults
        $this->apiKey     = config('services.payu.api_key', '4Vj8eK4r98P846E');
        $this->accountId  = config('services.payu.account_id', '512321');
        $this->test       = config('services.payu.test', 1);
        $this->url        = $this->test ? 'https://sandbox.checkout.payulatam.com/checkout/webcheckout.php' : 'https://checkout.payulatam.com/checkout/webcheckout.php';
    }

    public function generateSignature($reference, $amount, $currency)
    {
        // signature = md5(ApiKey~merchantId~referenceCode~amount~currency)
        // Note: For PayU, amount must be rounded or formatted correctly in signature
        $amountFormatted = number_format($amount, 0, '.', '');
        $signatureString = "{$this->apiKey}~{$this->merchantId}~{$reference}~{$amountFormatted}~{$currency}";
        return md5($signatureString);
    }

    public function getCheckoutParams($order)
    {
        $reference = "ORDER-{$order->id}-" . time();
        return [
            'merchantId'    => $this->merchantId,
            'accountId'     => $this->accountId,
            'description'   => "Compra en FusaShop - Pedido #{$order->id}",
            'referenceCode' => $reference,
            'amount'        => $order->total,
            'tax'           => 0,
            'taxReturnBase' => 0,
            'currency'      => 'COP',
            'signature'     => $this->generateSignature($reference, $order->total, 'COP'),
            'test'          => $this->test,
            'buyerEmail'    => $order->user->email,
            'responseUrl'   => route('payment.response'),
            'confirmationUrl' => route('payment.webhook'),
            'url'           => $this->url
        ];
    }
}
