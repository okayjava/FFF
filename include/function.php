<?php
$enc_passwd = "ABCDEABCDEABCDEABCDEABCDEABCDEAB";
$enc_iv = str_repeat(chr(99), 16);
function Encoder($value)
{  
    global $enc_passwd, $enc_iv;
    return base64_encode(openssl_encrypt($value, "aes-256-cbc", $enc_passwd, true, $enc_iv ));
}


/**
 * 대칭키 AES256 복호화
 * @author IMCORE.NET (http://www.imcore.net/encrypt-decrypt-aes256-c-objective-ios-iphone-ipad-php-java-android-perl-javascript/)
 * @param [type] $value 복호화 할 문자열.
 * @param [type] $key   복호화에 사용될 키값
 */
function Decoder($value)
{
    global $enc_passwd, $enc_iv;
    return openssl_decrypt(base64_decode($value), "aes-256-cbc", $enc_passwd, true, $enc_iv);
}

