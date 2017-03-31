<?php
function get_options()
{
    $options = array(
        'host' => '192.168.10.236',
        'port' => 5038,
        'username' => 'admin',
        'secret' => '18615ae90bd71af63f90664da14b2459',
        'connect_timeout' => 1000,
        'read_timeout' => 1000,
        'scheme' => 'tcp://' // try tls://
    );

    return $options;
}
