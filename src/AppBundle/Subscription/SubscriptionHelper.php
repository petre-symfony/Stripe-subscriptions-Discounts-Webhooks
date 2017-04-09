<?php

namespace AppBundle\Subscription;

use AppBundle\Entity\User;
use AppBundle\Entity\Subscription;

class SubscriptionHelper {
  /** @var SubscriptionPlan[] */
  private $plans = [];

  public function __construct() {
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
  }
}
