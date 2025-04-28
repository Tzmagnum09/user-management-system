<?php

namespace App\Repository;

use App\Entity\HashStorage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HashStorage>
 *
 * @method HashStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method HashStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method HashStorage[]    findAll()
 * @method HashStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HashStorageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HashStorage::class);
    }

    public function save(HashStorage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HashStorage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find or create a hash storage entity for a user
     */
    public function findOrCreateForUser(User $user): HashStorage
    {
        $hashStorage = $this->findOneBy(['user' => $user]);

        if (!$hashStorage) {
            $hashStorage = new HashStorage();
            $hashStorage->setUser($user);
        }

        return $hashStorage;
    }
}