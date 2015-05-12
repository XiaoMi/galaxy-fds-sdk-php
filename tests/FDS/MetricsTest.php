<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 4/1/15
 * Time: 10:17 AM
 */

namespace FDS\Test;

require_once(dirname(dirname(dirname(__FILE__))) . "/bootstrap.php");

use FDS\metrics\RequestMetrics;

class MetricsTest extends \PHPUnit_Framework_TestCase  {

  public function testRequestMetrics() {
    $action = "GetObject";
    $metricName = RequestMetrics::EXECUTION_TIME;

    $requestMetrics = new RequestMetrics($action);
    $requestMetrics->startEvent($metricName);
    sleep(1);
    $requestMetrics->endEvent($metricName);

    $clientMetrics = $requestMetrics->toClientMetrics();
    $metrics = $clientMetrics->getMetrics();
    $this->assertEquals(1, count($metrics));
    $this->assertEquals("GetObject.ExecutionTime", $metrics[0]->getMetricName());
    $this->assertEquals("Latency", $metrics[0]->getMetricType());
  }
}
