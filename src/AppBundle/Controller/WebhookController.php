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
    return new Response('baaaa');  
  }
}
