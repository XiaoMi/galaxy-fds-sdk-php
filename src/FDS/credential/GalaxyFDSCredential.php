<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 9:57 AM
 */

namespace FDS\credential;

abstract class GalaxyFDSCredential {

  public abstract function getGalaxyAccessId();

  public abstract function getGalaxyAccessSecret();
}