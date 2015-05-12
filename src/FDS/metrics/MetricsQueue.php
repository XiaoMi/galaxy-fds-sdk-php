<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 3/31/15
 * Time: 4:10 PM
 */

namespace FDS\metrics;

use Mutex;
use Stackable;

class MetricsQueue extends Stackable {
  public function __construct() {
    $this->mutex = Mutex::create();
    $this->clear();
  }

  public function add($metric_data) {
    Mutex::lock($this->mutex);
    $this[] = $metric_data;
    Mutex::unlock($this->mutex);
  }

  public function addAll($metric_data_array) {
    Mutex::lock($this->mutex);
    foreach($metric_data_array as $value) {
      $this[] = $value;
    }
    Mutex::unlock($this->mutex);
  }

  public function popAllMetrics() {
    Mutex::lock($this->mutex);
    $metrics = [];
    foreach ($this as $value) {
      $metrics[] = $value;
    }
    $this->clear();
    Mutex::unlock($this->mutex);
    return $metrics;
  }

  protected function clear() {
    foreach ($this as $i => $value) {
      unset($this[$i]);
    }
  }

  public function run() {
  }
}
