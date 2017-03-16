文件存储(FDS)PHP SDK使用介绍
=============================
##### 安装第三方依赖

    ./composer.phar install

##### 运行测试程序，如果测试程序运行成功，则说明环境配置正确:

    php ./examples/galaxy-fds.php (注意: 需要在样例中设置正确的App Key和Secret)

##### Release Notes:
* 20170316 - v1.0.1
    * Remove version field in composer config

* 20170309 - v1.0.0
    * Fix metadata not set in completeMultipartUpload
    * Add setEndpoint in FDSClientConfiguration

FDS PHP SDK User Guide
========================
##### Install dependencies

    ./composer.phar install

##### Run the example, if the example runs successfully, the environment is OK:

    php ./examples/galaxy-fds.php (Note: Users need to configure correct App Key and Secret)
