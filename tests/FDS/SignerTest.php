<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/25/14
 * Time: 3:15 PM
 */

namespace FDS\Test;

require_once(dirname(dirname(dirname(__FILE__))) . "/bootstrap.php");

use FDS\auth\Common;
use FDS\auth\signature\Signer;

class SignerTest extends \PHPUnit_Framework_TestCase {

  public function testGetExpires() {
    $uri = "/fds/mybucket/photos/puppy.jpg?test" .
      "&GalaxyAccessKeyId=AKIAIOSFODNN7EXAMPLE" .
      "&Expires=1141889120&Signature=vjbyPxybdZaNmGa%2ByT272YEAiv4%3D";
    $this->assertEquals(1141889120, Signer::getExpires($uri));
  }

  public function testStringStartsWith() {
    $hay_stack = "Hello world";
    $needle = "Hell";
    $this->assertTrue(Signer::stringStartsWith($hay_stack, $needle));
    $this->assertTrue(Signer::stringStartsWith($hay_stack, ""));
    $this->assertFalse(Signer::stringStartsWith($hay_stack, "ell"));
  }

  public function testGetHeaderValue() {
    $header = array(
      "Content-Type" => "application/json",
      "x-xiaomi-xx" => array("A", "B", "C"),
    );

    $this->assertEquals("application/json",
      Signer::getHeaderValue($header, "Content-Type"));
    $this->assertEquals("A", Signer::getHeaderValue($header, "x-xiaomi-xx"));
    $this->assertEquals("", Signer::getHeaderValue($header, "Content-MD5"));
  }

  public function testCanonicalizeXiaomiHeader() {
    $headers = NULL;
    $this->assertEquals("", Signer::canonicalizeXiaomiHeaders($headers));

    $headers = array();
    $headers["Content-Type"] = "application/json";
    $headers[Common::XIAOMI_HEADER_PREFIX . "meta-username"] =
      array("x@xiaomi.com", "a@xiaomi.com");
    $headers[Common::XIAOMI_HEADER_PREFIX . "date"] =
      "Tue, 27 Mar 2007 21:20:26+000";
    $this->assertEquals(
      Common::XIAOMI_HEADER_PREFIX . "date:" . "Tue, 27 Mar 2007 21:20:26+000\n" .
      Common::XIAOMI_HEADER_PREFIX . "meta-username:x@xiaomi.com,a@xiaomi.com\n",
      Signer::canonicalizeXiaomiHeaders($headers)
    );
  }

  public function testCanonicalizeResource() {
    $uri = "/fds/mybucket/?acl&a=1&b=2&c=3";
    $canonicalized_resource = Signer::canonicalizeResource($uri);
    $this->assertEquals("/fds/mybucket/?acl", $canonicalized_resource);

    $uri = "/fds/mybucket/test.txt?uploads&uploadId=xxx&partNumber=3&timestamp=12345566";
    $canonicalized_resource = Signer::canonicalizeResource($uri);
    $this->assertEquals("/fds/mybucket/test.txt?partNumber=3&uploadId=xxx&uploads",
      $canonicalized_resource);
  }

  public function testConstructStringToSign() {
    $http_method = "GET";
    $headers = NULL;
    $uri = "/fds/bucket/test.txt?uploads&uploadId=xx&partNumber=1";

    // No headers
    $this->assertEquals($http_method . "\n" .
      "\n" . // For Content-MD5
      "\n" . // For Content-Type
      "\n" . // For Date
      "" .   // For canonicalized  xiaomi headers
      "/fds/bucket/test.txt?partNumber=1&uploadId=xx&uploads",
      Signer::constructStringToSign($http_method, $uri, $headers));

    // Normal headers
    $headers = array();
    $headers[Common::CONTENT_TYPE] = "application/json";
    $headers[Common::CONTENT_MD5] = "123131331313231";
    $headers[Common::DATE] = "Tue, 27 Mar 2007 21:20:26+0000";
    $this->assertEquals($http_method . "\n" .
      $headers[Common::CONTENT_MD5] . "\n" .
      $headers[Common::CONTENT_TYPE] . "\n" .
      $headers[Common::DATE] . "\n" .
      "" .
      "/fds/bucket/test.txt?partNumber=1&uploadId=xx&uploads",
      Signer::constructStringToSign($http_method, $uri, $headers));

    // Xiaomi date overrides default 'Date'
    $headers[Common::XIAOMI_HEADER_PREFIX . "date"] =
      "Tue, 28 Mar 2007 21:20:26+0000";
    $this->assertEquals($http_method . "\n" .
      $headers[Common::CONTENT_MD5] . "\n" .
      $headers[Common::CONTENT_TYPE] . "\n" .
      "\n" . // Date is ignored
      Common::XIAOMI_HEADER_PREFIX . "date:Tue, 28 Mar 2007 21:20:26+0000\n" .
      "/fds/bucket/test.txt?partNumber=1&uploadId=xx&uploads",
      Signer::constructStringToSign($http_method, $uri, $headers));

    // Pre-signed uri
    $uri = "/fds/bucket/test.txt?GalaxyAccessKeyId=AKIAIOSFODNN7EXAMPLE"
      . "&Expires=1141889120&Signature=vjbyPxybdZaNmGa%2ByT272YEAiv4%3D";
    $this->assertEquals($http_method . "\n" .
      $headers[Common::CONTENT_MD5] . "\n" .
      $headers[Common::CONTENT_TYPE] . "\n" .
      "1141889120\n" . // Date is ignored
      Common::XIAOMI_HEADER_PREFIX . "date:Tue, 28 Mar 2007 21:20:26+0000\n" .
      "/fds/bucket/test.txt",
      Signer::constructStringToSign($http_method, $uri, $headers));
  }
}
