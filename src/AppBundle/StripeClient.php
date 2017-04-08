<?php

namespace AppBundle;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class StripeClient
{
    private $em;

    public function __construct($secretKey, EntityManager $em)
    {
        $this->em = $em;

        \Stripe\Stripe::setApiKey($secretKey);
    }

    public function createCustomer(User $user, $paymentToken)
    {
        $customer = \Stripe\Customer::create([
            'email' => $user->getEmail(),
            'source' => $paymentToken,
        ]);
        $user->setStripeCustomerId($customer->id);

        $this->em->persist($user);
        $this->em->flush($user);

        return $customer;
    }

    public function updateCustomerCard(User $user, $paymentToken)
    {
        $customer = \Stripe\Customer::retrieve($user->getStripeCustomerId());

        $customer->source = $paymentToken;
        $customer->save();
    }

    public function createInvoiceItem($amount, User $user, $description)
    {
        return \Stripe\InvoiceItem::create(array(
            "amount" => $amount,
            "currency" => "usd",
            "customer" => $user->getStripeCustomerId(),
            "description" => $description
        ));
    }

    public function createInvoice(User $user, $payImmediately = true)
    {
        $invoice = \Stripe\Invoice::create(array(
            "customer" => $user->getStripeCustomerId()
        ));

        if ($payImmediately) {
            // guarantee it charges *right* now
            $invoice->pay();
        }

        return $invoice;
    }
}
