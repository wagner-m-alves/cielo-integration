<?php

namespace App\Services;

use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Request\CieloRequestException;
use Illuminate\Support\Facades\Log;

class CieloService
{
    private $cielo;

    public function __construct()
    {
        $this->cielo = $this->setCielo();
    }

    private function setMerchant()
    {
        return new Merchant(config('cielo.merchantId'), config('cielo.merchantKey'));
    }

    private function setEnvironment()
    {
        return Environment::sandbox();
    }

    private function setCielo()
    {
        $merchant     = $this->setMerchant();
        $environment  = $this->setEnvironment();

        return new CieloEcommerce($merchant, $environment);
    }

    private function cancelSale()
    {
        return $this->cielo->cancelSale($paymentId, 15700);
    }

    public function run(string $orderIdentifier, string $customerFullName, $amount)
    {
        try
        {
            $sale = new Sale($orderIdentifier);
            $sale->customer($customerFullName);

            $payment = $sale->payment($amount);
            $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                        ->creditCard("123", CreditCard::VISA)
                        ->setExpirationDate("12/2018")
                        ->setCardNumber("0000000000000001")
                        ->setHolder("Fulano de Tal");

            $sale = $this->cielo->createSale($sale);

            $paymentId = $sale->getPayment()->getPaymentId();

            return $this->cielo->captureSale($paymentId, $amount, 0);
        }
        catch (CieloRequestException $e)
        {
            log::error($e);
        }
    }
}
