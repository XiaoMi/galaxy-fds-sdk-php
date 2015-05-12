<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 3/31/15
 * Time: 5:57 PM
 */

namespace FDS\model;

class ClientMetrics {
  public $metrics;

  public function __construct() {
    $this->metrics = array();
  }

  public function getMetrics() {
    return $this->metrics;
  }

  public function add($metric_data) {
    $this->metrics[] = $metric_data;
  }

  public function addAll($metric_data_array) {
    foreach($metric_data_array as $value) {
      $this->metrics[] = $value;
    }
  }
}

