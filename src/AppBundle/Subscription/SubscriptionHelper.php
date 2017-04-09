<?php

namespace AppBundle\Subscription;

use AppBundle\Entity\User;
use AppBundle\Entity\Subscription;
use Doctrine\ORM\EntityManager;

class SubscriptionHelper {
  /** @var SubscriptionPlan[] */
  private $plans = [];
  
  private $em;

  public function __construct(EntityManager $em) {
    $this->plans[] = new SubscriptionPlan(
      'farmer_brent_monthly',
      'Farmer Brent',
      99
    );
    
    $this->plans[] = new SubscriptionPlan(
      'new_zeelander_monthly',
      'New Zeelander',
      199
    );
    
    $this->em = $em;
  }

  /**
   * @param $planId
   * @return SubscriptionPlan|null
   */
  public function findPlan($planId) {
    foreach ($this->plans as $plan) {
      if ($plan->getPlanId() == $planId) {
        return $plan;
      }
    }
  }
  
  public function addSubscriptionToUser(\Stripe\Subscription $stripeSubscription, User $user) {
    $subscription = $user->getSubscription();
    if (!$subscription) {
      $subscription = new Subscription();
      $subscription->setUser($user);
    }
    
    $subscription->activateSubscription(
      $stripeSubscription->plan->id, 
      $stripeSubscription->id
    );
    
    $this->em->persist($subscription);
    $this->em->flush($subscription);
  }
  
  public function updateCardDetails(User $user, \Stripe\Customer $stripeCustomer) {
    $cardDetails = $stripeCustomer->sources->data[0];
    $user->setCardBrand($cardDetails->brand);
    $user->setCardLast4($cardDetails->last4);
    $this->em->persist($user);
    $this->em->flush($user);
  }
}
