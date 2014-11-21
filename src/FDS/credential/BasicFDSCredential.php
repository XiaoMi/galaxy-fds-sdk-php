<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 9:59 AM
 */

namespace FDS\credential;

class BasicFDSCredential extends  GalaxyFDSCredential {

  private $access_id;
  private $access_secret;

  public function __construct($access_id, $access_secret) {
    $this->access_id = $access_id;
    $this->access_secret = $access_secret;
  }

  public function getGalaxyAccessId()
  {
    return $this->access_id;
  }

  public function getGalaxyAccessSecret()
  {
    return $this->access_secret;
  }
}