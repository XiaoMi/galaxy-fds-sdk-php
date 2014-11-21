<?php
/**
 * Created by IntelliJ IDEA.
 * User: huang
 * Date: 5/21/14
 * Time: 6:29 PM
 */
namespace FDS\model;

final class QuotaType {
  const QPS = "QPS";
  const ThroughPut = "ThroughPut";
}

class Quota {
  public $type;
  public $action;
  public $value;

  function __construct($action, $type, $value) {
    $this->action = $action;
    $this->type = $type;
    $this->value = $value;
  }

  public function getAction() {
    return $this->action;
  }

  public function setAction($action) {
    $this->action = $action;
  }

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function getValue() {
    return $this->value;
  }

  public function setValue($value) {
    $this->value = $value;
  }
}