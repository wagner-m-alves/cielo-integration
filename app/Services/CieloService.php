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

    private function cancelSale($paymentId)
    {
        return $this->cielo->cancelSale($paymentId, 15700);
    }

    private function sale(string $orderIdentifier, array $customerData)
    {
        $sale = new Sale($orderIdentifier);

        $sale->customer($customerData['name'])
                    ->setIdentityType('CPF')
                    ->setIdentity($customerData['cpf'])
                    ->address()
                        ->setZipCode($customerData['postal_code'])
                        ->setStreet($customerData['street'])
                        ->setNumber($customerData['number'])
                        ->setDistrict($customerData['district'])
                        ->setCity($customerData['city'])
                        ->setState($customerData['state'])
                        ->setCountry($customerData['country']);

        return $sale;
    }

    private function selectCreditCardFlag(string $name)
    {
        $flags = [
            'visa'              => CreditCard::VISA,
            'master-card'       => CreditCard::MASTERCARD,
            'american-express'  => CreditCard::AMEX,
            'elo'               => CreditCard::ELO,
            'diners-club'       => CreditCard::DINERS,
            'discover'          => CreditCard::DISCOVER,
            'jcb'               => CreditCard::JCB,
            'aura'              => CreditCard::AURA,
        ];

        return $flags[$name];
    }

    private function creditCard(Sale $sale, $amount, array $creditCardData)
    {
        $sale->payment($amount)
                ->setType(Payment::PAYMENTTYPE_CREDITCARD)
                ->creditCard($creditCardData['cvv'], $this->selectCreditCardFlag($creditCardData['flag']))
                ->setExpirationDate($creditCardData['validity'])
                ->setCardNumber($creditCardData['number'])
                ->setHolder($creditCardData['holder']);
    }

    private function setBilletData(string $customerAddress, int $id)
    {
        return [
            'address'                   => $customerAddress,
            'number'                    => now()->format('ymd') . strval($id),
            'assignor'                  => 'Cyberage Technologies',
            'demonstrative'             => 'Texto Demonstrativo',
            'assignorIdentification'    => '14159741000154',
            'instructions'              => 'Não receber após o vencimento.',
        ];
    }

    private function billet(Sale $sale, $amount, array $billetData)
    {
        $sale->payment($amount)
                ->setType(Payment::PAYMENTTYPE_BOLETO)
                ->setAddress($billetData['address'])
                ->setBoletoNumber($billetData['number'])
                ->setAssignor($billetData['assignor'])
                ->setDemonstrative($billetData['demonstrative'])
                ->setExpirationDate(date('d/m/Y', strtotime('+1 month')))
                ->setIdentification($billetData['assignorIdentification'])
                ->setInstructions($billetData['instructions']);
    }

    public function generatePayment(string $orderIdentifier, array $customerData, string $method, $amount, array $creditCardData = [])
    {
        try
        {
            $sale       = $this->sale($orderIdentifier, $customerData);
            $response   = null;

            if($method == 'credit-card')
            {
                $this->creditCard($sale, $amount, $creditCardData);
            }
            else
            {
                $customerAddress    = $customerData['street'] . ', ' . $customerData['number'] . ' - ' . $customerData['district'] . ' - ' . $customerData['city'] . '/' . $customerData['state'];
                $data               = $this->setBilletData($customerAddress, rand(1,1000));

                $this->billet($sale, $amount, $data);
            }

            $response = $this->cielo->createSale($sale);

            $paymentId = $response->getPayment()->getPaymentId();

            if($method == 'credit-card')
                $response = $this->cielo->captureSale($paymentId, $amount, 0);

            return $response;
        }
        catch (CieloRequestException $e)
        {
            log::error($e);
        }
    }
}
