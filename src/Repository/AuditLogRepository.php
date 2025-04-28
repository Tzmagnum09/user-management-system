<?php

namespace App\Repository;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 *
 * @method AuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuditLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuditLog[]    findAll()
 * @method AuditLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuditLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find logs for a specific user.
     *
     * @return AuditLog[]
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by action.
     *
     * @return AuditLog[]
     */
    public function findByAction(string $action, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.action LIKE :action')
            ->setParameter('action', '%' . $action . '%') // Recherche partielle
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent logs.
     *
     * @return AuditLog[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find logs by type.
     *
     * @return AuditLog[]
     */
    public function findByType(string $type, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->setParameter('type', $type)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find logs with applied filters
     *
     * @param array $filters Les critères de filtrage
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return AuditLog[]
     */
    public function findWithFilters(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a');
        
        // Appliquer les filtres
        $this->applyFilters($qb, $filters);
        
        // Ajouter le tri par date décroissante
        $qb->orderBy('a.createdAt', 'DESC');
        
        // Ajouter la pagination
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Compter le nombre de logs avec les filtres appliqués
     *
     * @param array $filters Les critères de filtrage
     * @return int
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');
        
        // Appliquer les filtres
        $this->applyFilters($qb, $filters);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Supprime plusieurs logs en fonction de leurs IDs
     * 
     * @param array $ids Les IDs des logs à supprimer
     * @return int Le nombre de logs supprimés
     */
    public function deleteMultiple(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        // Supprimer les logs en utilisant une requête DQL directe
        $query = $this->getEntityManager()->createQuery(
            'DELETE FROM App\Entity\AuditLog a WHERE a.id IN (:ids)'
        );
        $query->setParameter('ids', $ids);
        
        return $query->execute();
    }
    
    /**
     * Appliquer les filtres au QueryBuilder
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        // Filtre par type
        if (!empty($filters['type'])) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $filters['type']);
        }
        
        // Filtre par action (recherche partielle)
        if (!empty($filters['action'])) {
            $qb->andWhere('a.action LIKE :action')
               ->setParameter('action', '%' . $filters['action'] . '%');
        }
        
        // Filtre par action exacte
        if (!empty($filters['action_exact'])) {
            $qb->andWhere('a.action = :action_exact')
               ->setParameter('action_exact', $filters['action_exact']);
        }
        
        // Filtre par pattern d'action
        if (!empty($filters['action_pattern'])) {
            $qb->andWhere('a.action LIKE :action_pattern')
               ->setParameter('action_pattern', $filters['action_pattern']);
        }
        
        // Filtre par utilisateur
        if (!empty($filters['user'])) {
            $qb->andWhere('a.user = :user')
               ->setParameter('user', $filters['user']);
        }
        
        // Filtre par adresse IP
        if (!empty($filters['ip'])) {
            $qb->andWhere('a.ipAddress LIKE :ip')
               ->setParameter('ip', '%' . $filters['ip'] . '%'); // Recherche partielle
        }
        
        // Filtre par navigateur
        if (!empty($filters['browser'])) {
            $qb->andWhere('a.browserName LIKE :browser')
               ->setParameter('browser', '%' . $filters['browser'] . '%'); // Recherche partielle
        }
        
        // Filtre par appareil
        if (!empty($filters['device'])) {
            $qb->andWhere('(a.deviceBrand LIKE :device OR a.deviceModel LIKE :device)')
               ->setParameter('device', '%' . $filters['device'] . '%'); // Recherche partielle
        }
        
        // Filtre par date de début
        if (!empty($filters['date_from'])) {
            $dateFrom = new \DateTimeImmutable($filters['date_from']);
            $qb->andWhere('a.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom->setTime(0, 0, 0));
        }
        
        // Filtre par date de fin
        if (!empty($filters['date_to'])) {
            $dateTo = new \DateTimeImmutable($filters['date_to']);
            $qb->andWhere('a.createdAt <= :dateTo')
               ->setParameter('dateTo', $dateTo->setTime(23, 59, 59));
        }
        
        // Filtre par recherche dans les détails
        if (!empty($filters['details'])) {
            $qb->andWhere('a.details LIKE :details')
               ->setParameter('details', '%' . $filters['details'] . '%'); // Recherche partielle
        }
    }
    
    /**
     * Obtenir des statistiques sur les logs
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total de logs
        $stats['total'] = $this->count([]);
        
        // Logs par type
        $stats['by_type'] = [];
        foreach (AuditLog::getAvailableTypes() as $type => $label) {
            $stats['by_type'][$type] = $this->count(['type' => $type]);
        }
        
        // Logs des dernières 24 heures
        $yesterday = new \DateTimeImmutable('-24 hours');
        $stats['last_24h'] = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Top 5 des utilisateurs avec le plus de logs
        $stats['top_users'] = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.user) as userId, COUNT(a.id) as logCount')
            ->groupBy('a.user')
            ->orderBy('logCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $stats;
    }
    
    /**
     * Trouver les logs d'erreurs HTTP
     *
     * @param string|null $errorCode Code d'erreur HTTP (404, 500, etc.)
     * @param int $limit Nombre maximum de résultats
     * @param int $offset Offset pour la pagination
     * @return AuditLog[]
     */
    public function findHttpErrors(?string $errorCode = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('a.action LIKE :pattern')
            ->setParameter('type', AuditLog::TYPE_ERROR)
            ->setParameter('pattern', 'http_error_%');
        
        if ($errorCode) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', 'http_error_' . strtolower($errorCode));
        }
        
        $qb->orderBy('a.createdAt', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Compter le nombre d'erreurs HTTP
     *
     * @param string|null $errorCode Code d'erreur HTTP (404, 500, etc.)
     * @return int
     */
    public function countHttpErrors(?string $errorCode = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.type = :type')
            ->andWhere('a.action LIKE :pattern')
            ->setParameter('type', AuditLog::TYPE_ERROR)
            ->setParameter('pattern', 'http_error_%');
        
        if ($errorCode) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', 'http_error_' . strtolower($errorCode));
        }
        
        return (int)$qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Obtenir des statistiques sur les erreurs HTTP
     *
     * @return array
     */
    public function getHttpErrorStats(): array
    {
        $stats = [];
        
        // Total d'erreurs HTTP
        $stats['total'] = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.type = :type')
            ->andWhere('a.action LIKE :pattern')
            ->setParameter('type', AuditLog::TYPE_ERROR)
            ->setParameter('pattern', 'http_error_%')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Erreurs par code
        $stats['by_code'] = $this->createQueryBuilder('a')
            ->select('SUBSTRING(a.action, 12) as code, COUNT(a.id) as count')
            ->where('a.type = :type')
            ->andWhere('a.action LIKE :pattern')
            ->setParameter('type', AuditLog::TYPE_ERROR)
            ->setParameter('pattern', 'http_error_%')
            ->groupBy('a.action')
            ->getQuery()
            ->getResult();
        
        // Dernières 24 heures
        $yesterday = new \DateTimeImmutable('-24 hours');
        $stats['last_24h'] = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.type = :type')
            ->andWhere('a.action LIKE :pattern')
            ->andWhere('a.createdAt >= :yesterday')
            ->setParameter('type', AuditLog::TYPE_ERROR)
            ->setParameter('pattern', 'http_error_%')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $stats;
    }
}