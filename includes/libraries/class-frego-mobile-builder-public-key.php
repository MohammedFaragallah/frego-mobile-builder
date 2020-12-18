<?php

use Firebase\JWT\JWT;

/**
 * Class Mobile_Builder_Public_Key.
 */
class Mobile_Builder_Public_Key
{




    /**
     * @param $kid
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getPublicKey($kid)
    {
        $publicKeys        = file_get_contents('https://appleid.apple.com/auth/keys');
        $decodedPublicKeys = json_decode($publicKeys, true);

        if (
            ! isset($decodedPublicKeys['keys']) ||
            count($decodedPublicKeys['keys']) < 1
        ) {
            throw new Exception(
                __('Invalid key format.', 'frego-mobile-builder')
            );
        }

        $key = array_search(
            $kid,
            array_column($decodedPublicKeys['keys'], 'kid')
        );

        if (false === $key) {
            throw new UnexpectedValueException(
                __(
                    '"kid" empty, unable to lookup correct key',
                    'frego-mobile-builder'
                )
            );
        }

        $parsedKeyData    = $decodedPublicKeys['keys'][ $key ];
        $parsedPublicKey  = self::parseKey($parsedKeyData);
        $publicKeyDetails = openssl_pkey_get_details($parsedPublicKey);

        if (! isset($publicKeyDetails['key'])) {
            throw new Exception(
                __('Invalid public key details.', 'frego-mobile-builder')
            );
        }

        return [
            'publicKey' => $publicKeyDetails['key'],
            'alg'       => $parsedKeyData['alg'],
        ];
    }

    /**
     * Parse a JWK key.
     *
     * @param array $jwk An individual JWK
     *
     * @throws InvalidArgumentException Provided JWK is empty
     * @throws UnexpectedValueException Provided JWK was invalid
     * @throws DomainException          OpenSSL failure
     *
     * @return array|resource An associative array that represents the key
     *
     * @uses createPemFromModulusAndExponent
     */
    private static function parseKey(array $jwk)
    {
        if (empty($jwk)) {
            throw new InvalidArgumentException(
                __('JWK must not be empty', 'frego-mobile-builder')
            );
        }
        if (! isset($jwk['kty'])) {
            throw new UnexpectedValueException(
                __('JWK must contain a "kty" parameter', 'frego-mobile-builder')
            );
        }

        switch ($jwk['kty']) {
            case 'RSA':
                if (\array_key_exists('d', $jwk)) {
                    throw new UnexpectedValueException(
                        __(
                            'RSA private keys are not supported',
                            'frego-mobile-builder'
                        )
                    );
                }
                if (! isset($jwk['n']) || ! isset($jwk['e'])) {
                    throw new UnexpectedValueException(
                        __(
                            'RSA keys must contain values for both "n" and "e"',
                            'frego-mobile-builder'
                        )
                    );
                }

                $pem       = self::createPemFromModulusAndExponent(
                    $jwk['n'],
                    $jwk['e']
                );
                $publicKey = \openssl_pkey_get_public($pem);
                if (false === $publicKey) {
                    throw new DomainException(
                        __('OpenSSL error: ', 'frego-mobile-builder') .
                        \openssl_error_string()
                    );
                }

                return $publicKey;
            default:
                // Currently only RSA is supported
                break;
        }
    }

    /**
     * Create a public key represented in PEM format from RSA modulus and exponent information.
     *
     * @param string $n The RSA modulus encoded in Base64
     * @param string $e The RSA exponent encoded in Base64
     *
     * @return string The RSA public key represented in PEM format
     *
     * @uses encodeLength
     */
    private static function createPemFromModulusAndExponent($n, $e)
    {
        $modulus       = JWT::urlsafeB64Decode($n);
        $publicExponent = JWT::urlsafeB64Decode($e);

        $components = [
            'modulus'        => \pack(
                'Ca*a*',
                2,
                self::encodeLength(\strlen($modulus)),
                $modulus
            ),
            'publicExponent' => \pack(
                'Ca*a*',
                2,
                self::encodeLength(\strlen($publicExponent)),
                $publicExponent
            ),
        ];

        $rsaPublicKey = \pack(
            'Ca*a*a*',
            48,
            self::encodeLength(
                \strlen($components['modulus']) +
                    \strlen($components['publicExponent'])
            ),
            $components['modulus'],
            $components['publicExponent']
        );

        // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
        $rsaOID       = \pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
        $rsaPublicKey = \chr(0) . $rsaPublicKey;
        $rsaPublicKey =
            \chr(3) .
            self::encodeLength(\strlen($rsaPublicKey)) .
            $rsaPublicKey;

        $rsaPublicKey = \pack(
            'Ca*a*',
            48,
            self::encodeLength(\strlen($rsaOID . $rsaPublicKey)),
            $rsaOID . $rsaPublicKey
        );

        return "-----BEGIN PUBLIC KEY-----\r\n" .
            \chunk_split(\base64_encode($rsaPublicKey), 64) .
            '-----END PUBLIC KEY-----';
    }

    /**
     * DER-encode the length.
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     *
     * @param int $length
     *
     * @return string
     */
    private static function encodeLength($length)
    {
        if ($length <= 0x7f) {
            return \chr($length);
        }

        $temp = \ltrim(\pack('N', $length), \chr(0));

        return \pack('Ca*', 0x80 | \strlen($temp), $temp);
    }
}