<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class ProfileController extends BaseController {
  /**
   * @Route("/profile", name="profile_account")
   */
  public function accountAction() {
    $currentPlan = null;
    $otherPlan = null;
    
    if ($this->getUser()->hasActiveSubscription()){
      $currentPlan = $this->get('subscription_helper')
        ->findPlan($this->getUser()->getSubscription()->getStripePlanId());
      
      $otherPlan = $this->get('subscription_helper')
        ->findPlanToChangeTo($currentPlan->getPlanId());
    }
    return $this->render('profile/account.html.twig', [
      'error' => null,
      'stripe_public_key' => $this->getParameter('stripe_public_key'),
      'current_plan' => $currentPlan,
      'otherPlan' => $otherPlan   
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
    
    try {
      $stripeClient = $this->get('stripe_client');
      $stripeCustomer = $stripeClient->updateCustomerCard($user, $token);
    } catch (\Stripe\Error\Card $e){
      $error = 'There was a problem charging your card ' . $e->getMessage(); 
      
      $this->addFlash('error', $error);
      
      return $this->redirectToRoute('profile_account');
    }
    
    $this->get('subscription_helper')->updateCardDetails($user, $stripeCustomer);
    
    $this->addFlash('success', 'Card Updated!');
    
    return $this->redirectToRoute('profile_account');
  }
  
  /**
   * @Route("/profile/plan/change/preview/{planId}", name="account_preview_plan_change")
   */
  public function previewPlanChangeAction($planId) {
    $plan = $this->get('subscription_helper')
      ->findPlan($planId);
    
    $stripeInvoice = $this->get('stripe_client')
      ->getUpcomingInvoiceForChangedSubscription(
        $this->getUser(),
        $plan
      );
    
    dump($stripeInvoice);
    
    return new JsonResponse(['total' => 50]);
  }
}
