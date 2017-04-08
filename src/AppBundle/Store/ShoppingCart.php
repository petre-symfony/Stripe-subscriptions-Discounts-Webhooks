<?php

namespace AppBundle\Store;

use AppBundle\Entity\Product;
use AppBundle\Subscription\SubscriptionHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

class ShoppingCart {
  const CART_PRODUCTS_KEY = '_shopping_cart.products';
  const CART_PLAN_KEY = '_shopping_cart.subscription_plan';
  const CART_COUPON_CODE_KEY = '_shopping_cart.coupon_code';
  const CART_COUPON_VALUE_KEY = '_shopping_cart.coupon_value';

  private $session;
  private $em;
  private $subscriptionHelper;

  private $products;

  public function __construct(Session $session, EntityManager $em, SubscriptionHelper $subscriptionHelper) {
    $this->session = $session;
    $this->em = $em;
    $this->subscriptionHelper = $subscriptionHelper;
  }

  public function addProduct(Product $product) {
    $products = $this->getProducts();

    if (!in_array($product, $products)) {
      $products[] = $product;
    }

    $this->updateProducts($products);
  }

  public function addSubscription($planId) {
    $this->session->set(
      self::CART_PLAN_KEY,
      $planId
    );
  }

  /**
   * @return Product[]
   */
  public function getProducts() {
    if ($this->products === null) {
      $productRepo = $this->em->getRepository('AppBundle:Product');
      $ids = $this->session->get(self::CART_PRODUCTS_KEY, []);
      $products = [];
      foreach ($ids as $id) {
        $product = $productRepo->find($id);

        // in case a product becomes deleted
        if ($product) {
          $products[] = $product;
        }
      }

      $this->products = $products;
    }

    return $this->products;
  }

  /**
   * @return \AppBundle\Subscription\SubscriptionPlan|null
   */
  public function getSubscriptionPlan() {
    $planId = $this->session->get(self::CART_PLAN_KEY);

    return $this->subscriptionHelper
      ->findPlan($planId);
  }

  public function getTotal() {
    $total = 0;
    foreach ($this->getProducts() as $product) {
      $total += $product->getPrice();
    }

    if ($this->getSubscriptionPlan()) {
      $price = $this->getSubscriptionPlan()
        ->getPrice();

      $total += $price;
    }

    return $total;
  }

  public function getTotalWithDiscount() {
    return max($this->getTotal() - $this->getCouponCodeValue(), 0);
  }

  public function setCouponCode($code, $value) {
    $this->session->set(
      self::CART_COUPON_CODE_KEY,
      $code
    );

    $this->session->set(
      self::CART_COUPON_VALUE_KEY,
      $value
    );
  }

  public function getCouponCode() {
    return $this->session->get(self::CART_COUPON_CODE_KEY);
  }

  public function getCouponCodeValue() {
    return $this->session->get(self::CART_COUPON_VALUE_KEY);
  }

  public function emptyCart() {
    $this->updateProducts([]);
    $this->updatePlanId(null);
    $this->setCouponCode(null, null);
  }

  /**
   * @param Product[] $products
   */
  private function updateProducts(array $products) {
    $this->products = $products;

    $ids = array_map(function(Product $item) {
        return $item->getId();
    }, $products);

    $this->session->set(self::CART_PRODUCTS_KEY, $ids);
  }

  private function updatePlanId($planId) {
    $this->session->set(self::CART_PLAN_KEY, $planId);
  }
}
