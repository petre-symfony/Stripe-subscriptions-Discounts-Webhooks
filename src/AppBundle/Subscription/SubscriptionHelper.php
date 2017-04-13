<?php

namespace AppBundle\Subscription;

use AppBundle\Entity\User;
use AppBundle\Entity\Subscription;
use AppBundle\Subscription\SubscriptionPlan;
use Doctrine\ORM\EntityManager;

class SubscriptionHelper {
  /** @var SubscriptionPlan[] */
  private $plans = [];
  
  private $em;

  public function __construct(EntityManager $em) {
    $this->plans[] = new SubscriptionPlan(
      'farmer_brent_monthly',
      'Farmer Brent',
      99,
      SubscriptionPlan::DURATION_MONTHLY      
    );
    
    $this->plans[] = new SubscriptionPlan(
      'farmer_brent_yearly',
      'Farmer Brent',
      990,
      SubscriptionPlan::DURATION_YEARLY     
    );
    
    $this->plans[] = new SubscriptionPlan(
      'new_zeelander_monthly',
      'New Zeelander',
      199,
      SubscriptionPlan::DURATION_MONTHLY     
    );
    
    $this->plans[] = new SubscriptionPlan(
      'new_zeelander_yearly',
      'New Zeelander',
      1990,
      SubscriptionPlan::DURATION_YEARLY    
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
  
  /**
   * 
   * @param $currentPlanId
   * @return SubscriptionPlan
   */
  public function findPlanToChangeTo($currentPlanId){
    if (strpos($currentPlanId, 'farmer_brent') !== false){
      $newPlanId = str_replace('farmer_brent', 'new_zeelander', $currentPlanId);
    } else {
      $newPlanId = str_replace('new_zeelander', 'farmer_brent', $currentPlanId);
    }
    
    return $this->findPlan($newPlanId);
  }

  public function addSubscriptionToUser(\Stripe\Subscription $stripeSubscription, User $user) {
    $subscription = $user->getSubscription();
    if (!$subscription) {
      $subscription = new Subscription();
      $subscription->setUser($user);
    }
    
    $periodEnd = \DateTime::createFromFormat('U', $stripeSubscription->current_period_end);
    
    $subscription->activateSubscription(
      $stripeSubscription->plan->id, 
      $stripeSubscription->id,
      $periodEnd      
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
  
  public function fullyCancelSubscription(Subscription $subscription) {
    $subscription->cancel();
    $this->em->persist($subscription);
    $this->em->flush($subscription);
  }
  
  public function handleSubscriptionPaid(Subscription $subscription, \Stripe\Subscription $stripeSubscription) {
     $newPeriodEnd = \DateTime::createFromFormat('U', $stripeSubscription->current_period_end); 
     
     //send an email if renewal
     $isRenewal = $newPeriodEnd > $subscription->getBillingPeriodEndsAt();
     
     $subscription->setBillingPeriodEndsAt($newPeriodEnd);
     
     $this->em->persist($subscription);
     $this->em->flush($subscription);
  }
}
