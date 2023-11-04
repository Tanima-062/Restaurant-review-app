<?php

namespace App\Libs;

/**
 * PHP7.2でmcryptが削除されたため代替用
 * Class Cipher
 * @package App\Lib
 */
class Cipher
{
    const ALGORITHM = 'des-ecb';
    const KEY = 'h3KFiAJFyMxTaRwc5ZdH';

    /**
     * @param $data
     * @return string
     */
    public static function encrypt($data)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ALGORITHM));

        $encrypted = openssl_encrypt(
            self::pkcs5Padding($data, self::ALGORITHM),
            self::ALGORITHM,
            self::KEY,
            OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
            $iv
        );

        return base64_encode($encrypted);
    }

    /**
     * @param $data
     * @return string
     */
    public static function decrypt($data)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ALGORITHM));

        $decoded = base64_decode($data);

        $decrypted = self::pkcs5Suppress(
            openssl_decrypt(
                $decoded,
                self::ALGORITHM,
                self::KEY,
                OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
                $iv
            )
        );

        return $decrypted;
    }

    private static function pkcs5Padding($text, $cryptMethod)
    {
        $block_size = self::opensslCipherBlockLength($cryptMethod);
        $pad = $block_size - (strlen($text) % $block_size);
        if ($block_size == $pad) { // padding不要
            return $text;
        }

        return $text . str_repeat(chr(0x00), $pad);
    }

    private static function pkcs5Suppress($text)
    {
        return rtrim($text);
    }

    private static function opensslCipherBlockLength($cipher)
    {
        switch (strtolower($cipher)) {
            case 'des-ecb':
            default:
                return 8;
        }
    }
}
