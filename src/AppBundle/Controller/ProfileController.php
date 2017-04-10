<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class ProfileController extends BaseController {
  /**
   * @Route("/profile", name="profile_account")
   */
  public function accountAction() {
    return $this->render('profile/account.html.twig', [
      'error' => null,
      'stripe_public_key' => $this->getParameter('stripe_public_key') 
    ]);
  }
  /**
   * @Route("/profile/subscription/cancel", name="account_subscription_cancel")
   * @Method("POST")
   */
  public function cancelSubscriptionAction() {
    $stripeClient = $this->get('stripe_client'); 
    $stripeSubscription = $stripeClient->cancelSubscription($this->getUser());
    
    $subscription = $this->getUser()->getSubscription();
    if ($stripeSubscription->status == 'canceled'){
      $subscription->cancel();
    } else {
      $subscription->deactivateSubscription();
    }
    
    $em = $this->getDoctrine()->getManager();
    $em->persist($subscription);
    $em->flush();
    
    $this->addFlash('success', 'Subscription Canceled :(');
    
    return $this->redirectToRoute('profile_account');
  }
  
  /**
   * @Route("/profile/subscription/reactivate", name="account_subscription_reactivate")
   */
  public function reactivateSubscriptionAction() { 
    $stripeClient = $this->get('stripe_client');
    $stripeSubscription = $stripeClient->reactivateSubscription($this->getUser());
    
    $this->get('subscription_helper')->addSubscriptionToUser($stripeSubscription, $this->getUser());
    
    $this->addFlash('success', 'Welcome back!');
    
    return $this->redirectToRoute('profile_account');
  }
  
  /**
   * @Route("/profile/cart/update", name="account_update_credit_card")
   * @Method("POST")
   */
  public function updateCreditCardAction(Request $request) {
    $token = $request->request->get('stripeToken');
    $user = $this->getUser();
    
    $stripeClient = $this->get('stripe_client');
    $stripeCustomer = $stripeClient->updateCustomerCard($user, $token);
  }
}
