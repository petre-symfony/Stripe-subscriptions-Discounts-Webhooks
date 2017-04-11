<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebhookControllerTest extends WebTestCase {
  private $container;
  /** @var EntityManager */
  private $em;

  public function setUp(){
    self::bootKernel();
    $this->container = self::$kernel->getContainer();
    $this->em = $this->container->get('doctrine')->getManager();
  }

  public function testStripeCustomerSubscriptionDeleted() {
    $subscription = $this->createSubscription();
    
    $eventJson = $this->getCustomerSubscriptionDeletedEvent(
      $subscription->getStripeSubscriptionId()      
    );
    
    $client = $this->createClient();
    $client->request(
      'POST', 
      '/webhooks/stripe',
      [],
      [],
      [],
      $eventJson      
    );
    dump($client->getResponse()->getContent());
    $this->assertEquals(200, $client->getResponse()->getStatusCode());
    
    $this->assertFalse($subscription->isActive());
  }
  
  private function createSubscription(){
    $user = new User();
    $user->setEmail('fluffy'.mt_rand().'@sheep.com');
    $user->setUsername('fluffy'.mt_rand());
    $user->setPlainPassword('baa');

    $subscription = new Subscription();
    $subscription->setUser($user);
    $subscription->activateSubscription(
        'plan_STRIPE_TEST_ABC'.mt_rand(),
        'sub_STRIPE_TEST_XYZ'.mt_rand(),
        new \DateTime('+1 month')
    );

    $this->em->persist($user);
    $this->em->persist($subscription);
    $this->em->flush();

    return $subscription;
  }
  
  private function getCustomerSubscriptionDeletedEvent($subscriptionId) {
    $json = <<<EOF 
{
  "created": 1326853478,
  "livemode": false,
  "id": "evt_00000000000000",
  "type": "customer.subscription.deleted",
  "object": "event",
  "request": null,
  "pending_webhooks": 1,
  "api_version": "2016-07-06",
  "data": {
    "object": {
      "id": "%s",
      "object": "subscription",
      "application_fee_percent": null,
      "cancel_at_period_end": true,
      "canceled_at": 1469731697,
      "created": 1469729305,
      "current_period_end": 1472407705,
      "current_period_start": 1469729305,
      "customer": "cus_00000000000000",
      "discount": null,
      "ended_at": 1470436151,
      "livemode": false,
      "metadata": {
      },
      "plan": {
        "id": "farmer_00000000000000",
        "object": "plan",
        "amount": 9900,
        "created": 1469720306,
        "currency": "usd",
        "interval": "month",
        "interval_count": 1,
        "livemode": false,
        "metadata": {
        },
        "name": "Farmer Brent (monthly)",
        "statement_descriptor": null,
        "trial_period_days": null
      },
      "quantity": 1,
      "start": 1469729305,
      "status": "canceled",
      "tax_percent": null,
      "trial_end": null,
      "trial_start": null
    }
  }
}            
EOF;
  
    return sprintf($json, $subscriptionId);        
  }
}
