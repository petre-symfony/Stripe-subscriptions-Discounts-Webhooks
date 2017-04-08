<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends BaseController
{
    /**
     * @Route("/cart/product/{slug}", name="order_add_product_to_cart")
     * @Method("POST")
     */
    public function addProductToCartAction(Product $product)
    {
        $this->get('shopping_cart')
            ->addProduct($product);

        $this->addFlash('success', 'Product added!');

        return $this->redirectToRoute('order_checkout');
    }

    /**
     * @Route("/cart/subscription/{planId}", name="order_add_subscription_to_cart")
     */
    public function addSubscriptionToCartAction($planId)
    {
        // todo - add the subscription plan to the cart!
    }

    /**
     * @Route("/checkout", name="order_checkout", schemes={"%secure_channel%"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function checkoutAction(Request $request)
    {
        $products = $this->get('shopping_cart')->getProducts();

        $error = false;
        if ($request->isMethod('POST')) {
            $token = $request->request->get('stripeToken');

            try {
                $this->chargeCustomer($token);
            } catch (\Stripe\Error\Card $e) {
                $error = 'There was a problem charging your card: '.$e->getMessage();
            }

            if (!$error) {
                $this->get('shopping_cart')->emptyCart();
                $this->addFlash('success', 'Order Complete! Yay!');

                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('order/checkout.html.twig', array(
            'products' => $products,
            'cart' => $this->get('shopping_cart'),
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
            'error' => $error,
        ));

    }

    /**
     * @param $token
     * @throws \Stripe\Error\Card
     */
    private function chargeCustomer($token)
    {
        $stripeClient = $this->get('stripe_client');
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->getStripeCustomerId()) {
            $stripeClient->createCustomer($user, $token);
        } else {
            $stripeClient->updateCustomerCard($user, $token);
        }

        $cart = $this->get('shopping_cart');

        foreach ($cart->getProducts() as $product) {
            $stripeClient->createInvoiceItem(
                $product->getPrice() * 100,
                $user,
                $product->getName()
            );
        }
        $stripeClient->createInvoice($user, true);
    }
}

