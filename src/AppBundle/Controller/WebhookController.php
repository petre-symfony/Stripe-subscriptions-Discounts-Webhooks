<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    $stripeEvent = $this->get('stripe_client')->findEvent($eventId);
    
    switch ($stripeEvent->type){
      case 'customer.subscription.deleted':
        // totdo - fully cancel the user's subscription
        break;
      default: 
        throw new \Exception('Unexpected webhook type form Stripe! '.$stripeEvent->type);
    }
    
    return new Response('Event handled: ' . $stripeEvent->type);  
  }
}
