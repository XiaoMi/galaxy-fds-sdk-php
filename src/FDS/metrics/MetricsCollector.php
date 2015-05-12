<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 3/27/15
 * Time: 10:08 AM
 */

namespace FDS\metrics;

use Thread;

class MetricsCollector {
  private $metrics_queue;
  private $metrics_uploader_thread;

  public function __construct($fds_client) {
    $this->metrics_queue = new MetricsQueue();
    $this->metrics_uploader_thread = new MetricsUploaderThread($fds_client,
        $this->metrics_queue);
  }

  public function __destruct() {
    $this->metrics_uploader_thread->kill();
  }

  public function start() {
    $this->metrics_uploader_thread->start();
  }

  public function collect($requestMetrics) {
    $this->metrics_queue->addAll($requestMetrics->toClientMetrics()
        ->getMetrics());
  }
}

