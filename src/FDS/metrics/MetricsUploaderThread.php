<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 3/30/15
 * Time: 8:08 PM
 */

namespace FDS\metrics;

use Exception;
use Thread;
use FDS\model\ClientMetrics;

class MetricsUploaderThread extends Thread {
  const UPLOAD_INTERVAL = 60;

  public function __construct($fds_client, $metrics_queue) {
    $this->fds_client = $fds_client;
    $this->metrics_queue = $metrics_queue;
  }

  public function run() {
    require(__DIR__ . "/../../../bootstrap.php");

    while (true) {
      try {
        $start_time = time();

        $client_metrics = new ClientMetrics();
        $metrics = $this->metrics_queue->popAllMetrics();
        $client_metrics->addAll($metrics);
        $this->fds_client->putClientMetrics($client_metrics);
        $this->fds_client->printResponse("Pushed " . count($metrics)
            . " metrics.\n");

        $end_time = time();
        $used_time = $end_time - $start_time;
        $left_time = self::UPLOAD_INTERVAL - $used_time;
        if ($left_time > 0) {
          sleep($left_time);
        } else {
          $this->fds_client->printResponse("Push metrics timeout, costs "
              . $used_time . " seconds\n");
        }
      } catch(Exception $e) {
        $this->fds_client->printResponse("Failed to push metrics, "
            . $e->getMessage());
      }
    }
  }
}



