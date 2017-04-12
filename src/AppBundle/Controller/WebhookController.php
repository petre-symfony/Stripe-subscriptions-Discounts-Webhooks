<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\StripeEventLog;

class WebhookController extends BaseController{
  /** 
   * @Route("/webhooks/stripe", name="webhook_stripe")
   */  
  public function stripeWebhookAction(Request $request){
    $data = json_decode($request->getContent(), true);
    
    if ($data == null){
      throw new Exception("Bad JSON body from Stripe!");
    }
    
    $eventId = $data['id'];
    
    $em = $this->getDoctrine()->getManager();
    $existingLog = $em->getRepository('AppBundle:StripeEventLog')
      ->findOneBy(['stripeEventId' => $eventId]);
    if ($existingLog) {
      return new Response('Event previously handled');
    }
    
    $log = new StripeEventLog($eventId);
    $em->persist($log);
    $em->flush($log);
    
    if ($this->getParameter('verify_stripe_event')){
      $stripeEvent = $this->get('stripe_client')->findEvent($eventId);
    } else {
      //fake the stripe_Event in the test environment
      $stripeEvent = json_decode($request->getContent());
    }
    
    
    $subscriptionHelper = $this->get('subscription_helper');
    switch ($stripeEvent->type){
      case 'customer.subscription.deleted':
        $stripeSubscriptionId = $stripeEvent->data->object->id;
        $subscription = $this->findSubscription($stripeSubscriptionId);
        
        $subscriptionHelper->fullyCancelSubscription($subscription);
        break;
      case 'invoice.payment_succeeded':
        $stripeSubscriptionId = $stripeEvent->data->object->subscription;
        
        if ($stripeSubscriptionId){
          $subscription = $this->findSubscription($stripeSubscriptionId);
          $stripeSubscription = $this->get('stripe_client')
            ->findSubscription($stripeSubscriptionId); 
          
          $subscriptionHelper->handleSubscriptionPaid($subscription, $stripeSubscription);
        }
        break;
      case 'invoice.payment_failed':
        $stripeSubscriptionId = $stripeEvent->data->object->id;
        
        if ($stripeSubscriptionId){
          $subscription = $this->findSubscription($stripeSubscriptionId);
          if($stripeEvent->data->object->attempt_count == 1){
            //todo send an email
            $user = $subscription->getUser();
          }
        }
        break;
      default:
        //allow this - we'll have Stripe send us everything
        //throw new \Exception('Unexpected webhook type form Stripe! '.$stripeEvent->type);
    }
    
    return new Response('Event handled: ' . $stripeEvent->type);  
  }
  
  /**
   * @param $stripeSubscriptionId
   * @return \AppBundle\Entity\Subscription
   * @throws \Exception
   */
  private function  findSubscription($stripeSubscriptionId){
    $subscription = $this->getDoctrine()
      ->getRepository('AppBundle:Subscription')
      ->findOneBy([
          'stripeSubscriptionId' => $stripeSubscriptionId 
      ]); 
    
    if (!$subscription) {
      throw new \Exception('Somehow we have no subscription id ' . $stripeSubscriptionId);
    }
    
    return $subscription;
  }
}
