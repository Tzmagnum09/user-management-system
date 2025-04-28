<?php

namespace App\Command;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-audit-log-device-info',
    description: 'Corriger les informations de dispositif dans les logs d\'audit existants',
)]
class FixAuditLogDeviceInfoCommand extends Command
{
    private EntityManagerInterface $entityManager;
    
    // Vos informations correctes hardcodées
    private const YOUR_REAL_BROWSER_NAME = 'Microsoft Edge';
    private const YOUR_REAL_BROWSER_VERSION = '135.0.3179.85';
    private const YOUR_REAL_OS_NAME = 'Windows';
    private const YOUR_REAL_OS_VERSION = '10 Enterprise 22H2';

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Correction des informations de dispositif dans les logs d\'audit existants');

        // Récupérer le repository des logs d'audit
        $logRepository = $this->entityManager->getRepository(AuditLog::class);
        
        // Récupérer tous les logs
        $logs = $logRepository->findAll();
        
        $count = 0;
        $errorCount = 0;
        
        // Boucle sur tous les logs pour les corriger
        foreach ($logs as $log) {
            try {
                // Mettre à jour les informations du dispositif
                $log->setOsName(self::YOUR_REAL_OS_NAME);
                $log->setOsVersion(self::YOUR_REAL_OS_VERSION);
                $log->setBrowserName(self::YOUR_REAL_BROWSER_NAME);
                $log->setBrowserVersion(self::YOUR_REAL_BROWSER_VERSION);
                
                // Incrémenter le compteur
                $count++;
                
                // Flush tous les 100 logs pour économiser la mémoire
                if ($count % 100 === 0) {
                    $this->entityManager->flush();
                    $io->info(sprintf('%d logs traités jusqu\'à présent...', $count));
                }
            } catch (\Exception $e) {
                $errorCount++;
                $io->error(sprintf('Erreur lors du traitement du log ID %d: %s', $log->getId(), $e->getMessage()));
            }
        }
        
        // Flush final pour s'assurer que tout est enregistré
        $this->entityManager->flush();
        
        $io->success(sprintf(
            'Terminé! %d logs d\'audit mis à jour avec succès. %d erreurs rencontrées.',
            $count,
            $errorCount
        ));

        return Command::SUCCESS;
    }
}