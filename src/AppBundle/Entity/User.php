<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $cardBrand;

    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $cardLast4;

    /**
     * @ORM\OneToOne(targetEntity="Subscription", mappedBy="user")
     */
    private $subscription;

    public function getId()
    {
        return $this->id;
    }

    public function getStripeCustomerId()
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId($stripeCustomerId)
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }

    /**
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    public function getCardBrand()
    {
        return $this->cardBrand;
    }

    public function setCardBrand($cardBrand)
    {
        $this->cardBrand = $cardBrand;
    }

    public function getCardLast4()
    {
        return $this->cardLast4;
    }

    public function setCardLast4($cardLast4)
    {
        $this->cardLast4 = $cardLast4;
    }
}
