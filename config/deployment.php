<?php

return [
    'ftp_host' => env('DEPLOY_FTP_HOST', ''),
    'ftp_user' => env('DEPLOY_FTP_USER', ''),
    'ftp_port' => env('DEPLOY_FTP_PORT', 21),
    'ftp_root' => env('DEPLOY_FTP_ROOT', '/public_html'),
    'protokol' => env('DEPLOY_PROTOKOL', 'ftp'),
];
