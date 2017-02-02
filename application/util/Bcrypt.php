<?php
/**
 * Bcrypt encryption for passwords for PHP.
 * Borrowed from Andrew Moore at: http://stackoverflow.com/questions/4795385/how-do-you-use-bcrypt-for-hashing-passwords-in-php
 * @author mmorrison
 */
class Util_Bcrypt {
    private $rounds;
    private $randomState;

    const DEFAULT_ROUNDS = 10;

    /**
     * @var Util_Bcrypt
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @param int $rounds How much extra entropy you want to add to the encryption.  note: this increases the
     *                    time to encrypt by a lot!
     * @return Util_Bcrypt
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if(CRYPT_BLOWFISH != 1) {
            throw new Exception('bcrypt not supported in this installation. See http://php.net/crypt');
        }

        if(($rounds = config('bcrypt')) === false) {
            $this->rounds = self::DEFAULT_ROUNDS;
        } else {
            $this->rounds = $rounds;
        }
    }

    public function getSalt() {
        $salt = sprintf('$2a$%02d$', $this->rounds);
        $bytes = $this->getRandomBytes(16);
        $salt .= $this->encodeBytes($bytes);

        return $salt;
    }

    private function getRandomBytes($count) {
        $bytes = '';

        if(function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($count);
        } else {
            throw new Exception('openssl_random_pseudo_bytes function required for bcrypt');
        }

        if($bytes === '' && is_readable('/dev/urandom')
        && ($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) {
            $bytes = fread($hRand, $count);
            fclose($hRand);
        }

        if(strlen($bytes) < $count) {
            $bytes = '';

            if($this->randomState === null) {
                $this->randomState = microtime();
                if(function_exists('getmypid')) {
                    $this->randomState .= getmypid();
                }
            }

            for($i = 0; $i < $count; $i += 16) {
                $this->randomState = md5(microtime() . $this->randomState);

                if (PHP_VERSION >= '5') {
                    $bytes .= md5($this->randomState, true);
                } else {
                    $bytes .= pack('H*', md5($this->randomState));
                }
            }

            $bytes = substr($bytes, 0, $count);
        }

        return $bytes;
    }

    private function encodeBytes($input) {
        // The following is code from the PHP Password Hashing Framework
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '';
        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }
}