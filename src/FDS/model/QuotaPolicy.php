<?php
/**
 * Created by IntelliJ IDEA.
 * User: huang
 * Date: 5/20/14
 * Time: 11:53 AM
 */

namespace FDS\model;

class QuotaPolicy {

  public $quotas;

  function __construct() {
    $this->quotas = array();
  }

  public static function fromJson($json) {
    if (is_object($json)) {
      $policy = new QuotaPolicy();
      if (isset($json->quotas)) {
        $policy->setQuotas($json->quotas);
      }
      return $policy;
    }
    return NULL;
  }

  public function getQuotas() {
    return $this->quotas;
  }

  public function setQuotas($quotas) {
    $this->quotas = $quotas;
  }

  public function addQuota($quota) {
    if ($quota instanceof Quota) {
      $this->quotas[] = $quota;
    }
  }
}