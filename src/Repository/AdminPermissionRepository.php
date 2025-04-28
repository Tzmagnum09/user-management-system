<?php

namespace App\Repository;

use App\Entity\AdminPermission;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminPermission>
 *
 * @method AdminPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminPermission[]    findAll()
 * @method AdminPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPermission::class);
    }

    public function save(AdminPermission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AdminPermission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find permissions for an admin.
     *
     * @return AdminPermission[]
     */
    public function findByAdmin(User $admin): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.admin = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('a.permission', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find admins with a specific permission.
     *
     * @return User[]
     */
    public function findAdminsWithPermission(string $permission): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT admin')
            ->from(User::class, 'admin')
            ->join('a.admin', 'admin')
            ->where('a.permission = :permission')
            ->andWhere('a.isGranted = :isGranted')
            ->setParameter('permission', $permission)
            ->setParameter('isGranted', true)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Remove all permissions for a specific user
     */
    public function removeAllForUser(User $user): int
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->delete()
            ->where('a.admin = :admin')
            ->setParameter('admin', $user);
        
        return $queryBuilder->getQuery()->execute();
    }
}