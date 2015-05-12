<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 3/27/15
 * Time: 10:08 AM
 */

namespace FDS\metrics;

use FDS\model\ClientMetrics;
use FDS\model\MetricData;

class RequestMetrics {
  const EXECUTION_TIME = "ExecutionTime";

  private $action;
  private $metrics;

  public function __construct($action) {
    $this->action = $action;
    $this->metrics = array();
  }

  public function startEvent($metric_name) {
    $timing_info = new TimeInfo($this->millitime(), null);
    $this->metrics[$metric_name] = $timing_info;
  }

  public function endEvent($metric_name) {
    $timing_info = $this->metrics[$metric_name];
    $timing_info->setEndTimeMilli($this->millitime());
  }

  public function toClientMetrics() {
    $client_metrics = new ClientMetrics();

    foreach($this->metrics as $key => $value) {
      $metric_data = new MetricData();
      if ($key == self::EXECUTION_TIME) {
        $metric_data->setMetricName($this->action . "." . $key);
        $metric_data->setMetricType(MetricData::LATENCY);
        $metric_data->setValue($value->getEndTimeMilli() -
            $value->getStartTimeMilli());
        $metric_data->setTimeStamp(round($value->getEndTimeMilli() / 1000));
      }
      $client_metrics->add($metric_data);
    }

    return $client_metrics;
  }

  private function millitime() {
    return round(microtime(true) * 1000);
  }
}

class TimeInfo {
  private $start_time_milli;
  private $end_time_milli;

  public function __construct($start_time_milli, $end_time_milli) {
    $this->start_time_milli = $start_time_milli;
    $this->end_time_milli = $end_time_milli;
  }

  public function getStartTimeMilli() {
    return $this->start_time_milli;
  }

  public function setStartTimeMilli($start_time_milli) {
    $this->start_time_milli = $start_time_milli;
  }

  public function getEndTimeMilli() {
    return $this->end_time_milli;
  }

  public function setEndTimeMilli($end_time_milli) {
    $this->end_time_milli = $end_time_milli;
  }
}
