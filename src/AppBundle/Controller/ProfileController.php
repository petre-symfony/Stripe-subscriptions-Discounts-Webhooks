<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class ProfileController extends BaseController {
  /**
   * @Route("/profile", name="profile_account")
   */
  public function accountAction() {
    return $this->render('profile/account.html.twig');
  }
  /**
   * @Route("/profile/subscription/cancel", name="account_subscription_cancel")
   * @Method("POST")
   */
  public function cancelSubscriptionAction() {
    $stripeClient = $this->get('stripe_client'); 
    $stripeClient->cancelSubscription($this->getUser());
    
    $subscription = $this->getUser()->getSubscription();
    $subscription->deactivateSubscription();
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
  }
}
