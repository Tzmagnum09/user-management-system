<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 *
 * @method EmailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailTemplate[]    findAll()
 * @method EmailTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    public function save(EmailTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find a template by code and locale.
     */
    public function findByCodeAndLocale(string $code, string $locale): ?EmailTemplate
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.code = :code')
            ->andWhere('e.locale = :locale')
            ->setParameter('code', $code)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find templates by code for all locales.
     *
     * @return EmailTemplate[]
     */
    public function findByCode(string $code): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.code = :code')
            ->setParameter('code', $code)
            ->orderBy('e.locale', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all unique template codes.
     *
     * @return array
     */
    public function findAllCodes(): array
    {
        $results = $this->createQueryBuilder('e')
            ->select('DISTINCT e.code')
            ->orderBy('e.code', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'code');
    }
}
