<?php

namespace App\Services;

use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\{
    Environment,
    Sale,
    CieloEcommerce,
    Payment,
    CreditCard,
    RecurrentPayment,
};
use Cielo\API30\Ecommerce\Request\CieloRequestException;
use Exception;
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

    private function sale(string $orderIdentifier, array $customerData)
    {
        $sale = new Sale($orderIdentifier);

        $sale->customer($customerData['name'])
                    ->setIdentityType('CPF') // Mude para CNPJ, caso seja Pessoa Jurídica
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

    private function billet(Sale $sale, $amount, string $customerAddress, int $id)
    {
        $sale->payment($amount)
                ->setType(Payment::PAYMENTTYPE_BOLETO)
                ->setAddress($customerAddress)
                ->setBoletoNumber(now()->format('ymd') . strval($id))
                ->setAssignor(config('payment.billet.assignor'))
                ->setDemonstrative(config('payment.billet.demonstrative'))
                ->setExpirationDate(date('d/m/Y', strtotime('+1 month')))
                ->setIdentification(config('payment.billet.assignorIdentification'))
                ->setInstructions(config('payment.billet.instructions'));
    }

    public function generatePayment(string $orderIdentifier, array $customerData, string $method, $amount, array $creditCardData = [], bool $recurrent = false, string $interval = 'monthly')
    {
        try
        {
            $sale               = $this->sale($orderIdentifier, $customerData);
            $customerAddress    = $customerData['street'] . ', ' . $customerData['number'] . ' - ' . $customerData['district'] . ' - ' . $customerData['city'] . '/' . $customerData['state'];
            $response           = null;

            if($method == 'credit-card' && !is_null($creditCardData))
                $this->creditCard($sale, $amount, $creditCardData, $recurrent, $interval);
            elseif($method == 'billet')
                $this->billet($sale, $amount, $customerAddress, rand(1,1000));
            else
                return;

            $response = $this->cielo->createSale($sale);

            $paymentId = $response->getPayment()->getPaymentId();

            if($method == 'credit-card')
                $response = $this->cielo->captureSale($paymentId, $amount, 0);

            return $response;
        }
        catch (CieloRequestException $e)
        {
            log::error($e);
            return;
        }
        catch (Exception $e)
        {
            log::error($e);
            return;
        }
    }

    public function cancel($paymentId, $amount)
    {
        try
        {
            return $this->cielo->cancelSale($paymentId, $amount);
        }
        catch (CieloRequestException $e)
        {
            log::error($e);
            return;
        }
        catch (Exception $e)
        {
            log::error($e);
            return;
        }
    }
}
