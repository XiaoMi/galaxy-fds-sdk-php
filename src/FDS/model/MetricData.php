<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 3/31/15
 * Time: 6:00 PM
 */

namespace FDS\model;

class MetricData {

  const LATENCY = "Latency";
  const THROUGHPUT = "Throughput";
  const Counter = "Counter";

  // Keep this kind of naming for json serialization.
  public $metricType;
  public $metricName;
  public $value;
  public $timestamp;

  public function getMetricType() {
    return $this->metricType;
  }

  public function setMetricType($metricType) {
    $this->metricType = $metricType;
  }

  public function getMetricName() {
    return $this->metricName;
  }

  public function setMetricName($metricName) {
    $this->metricName = $metricName;
  }

  public function getValue() {
    return $this->value;
  }

  public function setValue($value) {
    $this->value = $value;
  }

  public function getTimeStamp() {
    return $this->timestamp;
  }

  public function setTimeStamp($timestamp) {
    $this->timestamp = $timestamp;
  }

  public function __toString() {
    return "[" . $this->metricType . ", " . $this->metricName . ", "
      . $this->value . ", " . $this->timestamp . "]";
  }
}
