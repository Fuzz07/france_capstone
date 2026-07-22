<?php

if (!function_exists('clean_env_val')) {
    function clean_env_val($value) {
        if (is_string($value)) {
            // Strip UTF-8 BOM (\ufeff) and control characters
            $bom = pack('H*', 'EFBBBF');
            $value = preg_replace("/^$bom/", '', $value);
            $value = str_replace("\ufeff", '', $value);
            // Trim quotes and whitespace
            return trim($value, "\"' \t\n\r\0\x0B");
        }
        return $value;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => clean_env_val(env('MAIL_MAILER', 'smtp')),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover", "roundrobin"
    |
    */

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => clean_env_val(env('MAIL_HOST', 'smtp.gmail.com')),
            'port' => clean_env_val(env('MAIL_PORT', 587)),
            'encryption' => clean_env_val(env('MAIL_ENCRYPTION', 'tls')),
            'username' => clean_env_val(env('MAIL_USERNAME', 'merasstore@gmail.com')),
            'password' => clean_env_val(env('MAIL_PASSWORD', 'SROPBDiAJ$')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -el'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => clean_env_val(env('MAIL_FROM_ADDRESS', 'merasstore@gmail.com')),
        'name' => clean_env_val(env('MAIL_FROM_NAME', "Mera's Store")),
    ],

];
