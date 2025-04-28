<?php

namespace App\Service;

use App\Entity\HashStorage;
use App\Entity\User;
use App\Repository\HashStorageRepository;
use Doctrine\ORM\EntityManagerInterface;

class PasswordHashService
{
    private EntityManagerInterface $entityManager;
    private HashStorageRepository $hashStorageRepository;
    
    // Options de hashage standards
    private const BCRYPT_COST = 10;
    private const SCRYPT_FACTORS = ['cost' => 16384, 'block_size' => 8, 'parallelization' => 1];
    private const ARGON2_OPTIONS = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1];
    private const PBKDF2_ITERATIONS = 10000;
    private const HASH_KEY_LENGTH = 32;

    public function __construct(
        EntityManagerInterface $entityManager,
        HashStorageRepository $hashStorageRepository
    ) {
        $this->entityManager = $entityManager;
        $this->hashStorageRepository = $hashStorageRepository;
    }

    /**
     * Generate all hash variants for a given plain password and store them for a user
     */
    public function storeAllPasswordHashes(User $user, string $plainPassword): void
    {
        // Find existing hash storage or create a new one
        $hashStorage = $this->hashStorageRepository->findOrCreateForUser($user);
        
        // Generate all the hash variants
        $hashStorage->setMd5($this->hashMd5($plainPassword));
        $hashStorage->setSha1($this->hashSha1($plainPassword));
        $hashStorage->setSha224($this->hashSha224($plainPassword));
        $hashStorage->setSha256($this->hashSha256($plainPassword));
        $hashStorage->setSha384($this->hashSha384($plainPassword));
        $hashStorage->setSha512($this->hashSha512($plainPassword));
        $hashStorage->setSha3($this->hashSha3($plainPassword));
        $hashStorage->setBcrypt($this->hashBcrypt($plainPassword));
        $hashStorage->setScrypt($this->hashScrypt($plainPassword));
        $hashStorage->setArgon2($this->hashArgon2($plainPassword));
        $hashStorage->setArgon2i($this->hashArgon2i($plainPassword));
        $hashStorage->setArgon2d($this->hashArgon2d($plainPassword));
        $hashStorage->setArgon2id($this->hashArgon2id($plainPassword));
        $hashStorage->setPbkdf2($this->hashPbkdf2($plainPassword));
        $hashStorage->setWhirlpool($this->hashWhirlpool($plainPassword));
        $hashStorage->setNtlm($this->hashNtlm($plainPassword));
        $hashStorage->setBlowfish($this->hashBlowfish($plainPassword));
        $hashStorage->setCrypt($this->hashCrypt($plainPassword));
        
        // Save the hash storage entity
        $this->entityManager->persist($hashStorage);
        $this->entityManager->flush();
        
        // Ensure the user has a reference to the hash storage
        if ($user->getHashStorage() !== $hashStorage) {
            $user->setHashStorage($hashStorage);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
    
    /**
     * MD5 hash (insecure, for compatibility only)
     */
    private function hashMd5(string $plainPassword): string
    {
        return md5($plainPassword);
    }
    
    /**
     * SHA-1 hash (insecure, for compatibility only)
     */
    private function hashSha1(string $plainPassword): string
    {
        return sha1($plainPassword);
    }
    
    /**
     * SHA-224 hash
     */
    private function hashSha224(string $plainPassword): string
    {
        return hash('sha224', $plainPassword);
    }
    
    /**
     * SHA-256 hash
     */
    private function hashSha256(string $plainPassword): string
    {
        return hash('sha256', $plainPassword);
    }
    
    /**
     * SHA-384 hash
     */
    private function hashSha384(string $plainPassword): string
    {
        return hash('sha384', $plainPassword);
    }
    
    /**
     * SHA-512 hash
     */
    private function hashSha512(string $plainPassword): string
    {
        return hash('sha512', $plainPassword);
    }
    
    /**
     * SHA-3 hash (SHA3-256)
     */
    private function hashSha3(string $plainPassword): string
    {
        // If SHA3 is not available, fallback to SHA-256
        if (in_array('sha3-256', hash_algos())) {
            return hash('sha3-256', $plainPassword);
        }
        
        return hash('sha256', $plainPassword);
    }
    
    /**
     * Bcrypt hash (the default for many modern systems)
     */
    private function hashBcrypt(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }
    
    /**
     * Scrypt hash
     */
    private function hashScrypt(string $plainPassword): string
    {
        // For PHP 7.4+ - if not available, fallback to Bcrypt
        if (defined('PASSWORD_ARGON2ID')) {
            if (defined('PASSWORD_SCRYPT')) {
                return password_hash($plainPassword, PASSWORD_SCRYPT, self::SCRYPT_FACTORS);
            }
            
            // Fallback for older PHP versions without Scrypt
            return $this->hashBcrypt($plainPassword);
        }
        
        // Fallback for much older PHP versions
        return password_hash($plainPassword, PASSWORD_BCRYPT);
    }
    
    /**
     * Argon2 hash (generic)
     */
    private function hashArgon2(string $plainPassword): string
    {
        // In PHP, this is essentially Argon2i - if PHP updates to support other Argon2 variants directly, update here
        return $this->hashArgon2i($plainPassword);
    }
    
    /**
     * Argon2i hash
     */
    private function hashArgon2i(string $plainPassword): string
    {
        if (defined('PASSWORD_ARGON2I')) {
            return password_hash($plainPassword, PASSWORD_ARGON2I, self::ARGON2_OPTIONS);
        }
        
        // Fallback to bcrypt for PHP versions without Argon2i
        return $this->hashBcrypt($plainPassword);
    }
    
    /**
     * Argon2d hash - Note: In PHP, this is approximated as we don't have direct support
     */
    private function hashArgon2d(string $plainPassword): string
    {
        // Since PHP doesn't have direct Argon2d support, we'll salt it differently than Argon2i
        // This is not a real Argon2d implementation but gives us distinct hash values
        if (defined('PASSWORD_ARGON2I')) {
            $options = self::ARGON2_OPTIONS;
            return password_hash('argon2d_' . $plainPassword, PASSWORD_ARGON2I, $options);
        }
        
        // Fallback to regular hash
        return hash('sha512', 'argon2d_' . $plainPassword);
    }
    
    /**
     * Argon2id hash
     */
    private function hashArgon2id(string $plainPassword): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plainPassword, PASSWORD_ARGON2ID, self::ARGON2_OPTIONS);
        }
        
        // Fallback if Argon2id isn't available (PHP < 7.3)
        return $this->hashArgon2i($plainPassword);
    }
    
    /**
     * PBKDF2 hash
     */
    private function hashPbkdf2(string $plainPassword): string
    {
        // Using a fixed salt for demonstration - in practice use a random salt per user
        $salt = 'pbkdf2_fixed_salt';
        return hash_pbkdf2('sha256', $plainPassword, $salt, self::PBKDF2_ITERATIONS, self::HASH_KEY_LENGTH * 2);
    }
    
    /**
     * Whirlpool hash
     */
    private function hashWhirlpool(string $plainPassword): string
    {
        return hash('whirlpool', $plainPassword);
    }
    
    /**
     * NTLM hash (used in Windows environments)
     */
    private function hashNtlm(string $plainPassword): string
    {
        // NTLM uses MD4 hash of UTF-16LE encoded password
        $utf16Password = iconv('UTF-8', 'UTF-16LE', $plainPassword);
        if ($utf16Password === false) {
            // If conversion fails, fallback to a basic approximation
            return md5('ntlm_' . $plainPassword);
        }
        
        return bin2hex(mhash(MHASH_MD4, $utf16Password));
    }
    
    /**
     * Blowfish hash (similar to bcrypt but with specific format)
     */
    private function hashBlowfish(string $plainPassword): string
    {
        return crypt($plainPassword, '$2a$' . sprintf('%02d', self::BCRYPT_COST) . '$' . $this->generateBlowfishSalt());
    }
    
    /**
     * Generate a valid Blowfish salt (22 characters)
     */
    private function generateBlowfishSalt(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';
        $salt = '';
        for ($i = 0; $i < 22; $i++) {
            $salt .= $chars[random_int(0, 63)];
        }
        return $salt;
    }
    
    /**
     * Traditional Unix crypt hash
     */
    private function hashCrypt(string $plainPassword): string
    {
        // Use SHA-512 crypt if available (modern Linux systems)
        if (CRYPT_SHA512 == 1) {
            return crypt($plainPassword, '$6$' . $this->generateSalt(16));
        }
        
        // Fallback to standard DES crypt
        return crypt($plainPassword, $this->generateSalt(2));
    }
    
    /**
     * Generate a random salt for crypt functions
     */
    private function generateSalt(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./';
        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $salt;
    }
}