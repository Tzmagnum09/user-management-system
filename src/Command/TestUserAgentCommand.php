<?php

namespace App\Command;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-user-agent',
    description: 'Teste la détection des informations à partir d\'un User-Agent',
)]
class TestUserAgentCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        string $name = null
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user-agent', InputArgument::OPTIONAL, 'Le User-Agent à analyser', null)
            ->addArgument('ua-file', InputArgument::OPTIONAL, 'Fichier contenant des User-Agents pour analyse par lot', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userAgent = $input->getArgument('user-agent');
        $uaFile = $input->getArgument('ua-file');

        // Récupérer un utilisateur pour le test
        $user = $this->userRepository->findOneBy([], ['id' => 'ASC']);
        if (!$user) {
            $io->error('Aucun utilisateur trouvé dans la base de données');
            return Command::FAILURE;
        }

        // Tester un seul User-Agent
        if ($userAgent) {
            $this->testSingleUserAgent($io, $userAgent, $user);
            return Command::SUCCESS;
        }

        // Tester un fichier de User-Agents
        if ($uaFile) {
            $this->testUserAgentsFromFile($io, $uaFile, $user);
            return Command::SUCCESS;
        }

        // Tester les User-Agents courants
        $this->testCommonUserAgents($io, $user);
        return Command::SUCCESS;
    }

    private function testSingleUserAgent(SymfonyStyle $io, string $userAgent, User $user): void
    {
        $io->title('Test de détection du User-Agent');
        $io->text('User-Agent: ' . $userAgent);

        $auditLog = new AuditLog();
        $auditLog->setUser($user);
        $auditLog->setAction('test_user_agent');
        $auditLog->setIpAddress('127.0.0.1');
        $auditLog->setUserAgent($userAgent);

        // Analyser le User-Agent avec la méthode de réflexion
        $this->callParseUserAgent($auditLog, $userAgent);

        // Afficher les résultats
        $this->displayResults($io, $auditLog);
    }

    private function testUserAgentsFromFile(SymfonyStyle $io, string $uaFile, User $user): void
    {
        $io->title('Test de détection à partir du fichier ' . $uaFile);

        if (!file_exists($uaFile)) {
            $io->error('Le fichier ' . $uaFile . ' n\'existe pas');
            return;
        }

        $userAgents = file($uaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($userAgents as $userAgent) {
            $io->section('User-Agent: ' . $userAgent);

            $auditLog = new AuditLog();
            $auditLog->setUser($user);
            $auditLog->setAction('test_user_agent');
            $auditLog->setIpAddress('127.0.0.1');
            $auditLog->setUserAgent($userAgent);

            // Analyser le User-Agent
            $this->callParseUserAgent($auditLog, $userAgent);

            // Afficher les résultats
            $this->displayResults($io, $auditLog);
            $io->newLine();
        }
    }

    private function testCommonUserAgents(SymfonyStyle $io, User $user): void
    {
        $io->title('Test des User-Agents courants');

        $userAgents = [
            // Chrome (Windows 10)
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            
            // Chrome (Windows 11)
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            
            // Firefox (Windows)
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            
            // Microsoft Edge
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
            
            // Safari (macOS)
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            
            // Chrome (macOS)
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
            
            // Chrome (Android)
            'Mozilla/5.0 (Linux; Android 11; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
            
            // Safari (iOS)
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            
            // Chrome (Linux)
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
            
            // Firefox (Linux)
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0',
            
            // Opera
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 OPR/77.0.4054.90',
            
            // Samsung Internet
            'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.0 Chrome/87.0.4280.141 Mobile Safari/537.36',
        ];
        
        foreach ($userAgents as $userAgent) {
            $io->section('User-Agent: ' . $userAgent);

            $auditLog = new AuditLog();
            $auditLog->setUser($user);
            $auditLog->setAction('test_user_agent');
            $auditLog->setIpAddress('127.0.0.1');
            $auditLog->setUserAgent($userAgent);

            // Analyser le User-Agent
            $this->callParseUserAgent($auditLog, $userAgent);

            // Afficher les résultats
            $this->displayResults($io, $auditLog);
            $io->newLine();
        }
    }

    private function callParseUserAgent(AuditLog $auditLog, string $userAgent): void
    {
        try {
            // Utiliser la réflexion pour appeler la méthode parseUserAgent de AuditLogger
            $reflectionClass = new \ReflectionClass(\App\Service\AuditLogger::class);
            $reflectionMethod = $reflectionClass->getMethod('parseUserAgent');
            $reflectionMethod->setAccessible(true);
            
            // Créer une instance temporaire d'AuditLogger pour appeler la méthode
            $instance = $reflectionClass->newInstanceWithoutConstructor();
            
            // Appeler la méthode parseUserAgent
            $reflectionMethod->invoke($instance, $userAgent, $auditLog);
        } catch (\Throwable $e) {
            // En cas d'erreur utiliser une méthode de secours basée sur la même logique
            $this->parseUserAgentFallback($auditLog, $userAgent);
        }
    }
    
    private function parseUserAgentFallback(AuditLog $auditLog, string $userAgent): void
    {
        try {
            // Valeurs par défaut
            $deviceBrand = 'Unknown';
            $deviceModel = 'Unknown';
            $osName = 'Unknown';
            $osVersion = 'Unknown';
            $browserName = 'Unknown';
            $browserVersion = 'Unknown';
            
            // Détection du navigateur - Commencer par les plus spécifiques
            
            // Microsoft Edge
            if (preg_match('/Edg(?:e|A|iOS)?\/([0-9\.]+)/', $userAgent, $matches)) {
                $browserName = 'Microsoft Edge';
                $browserVersion = $matches[1];
            }
            // Opera
            elseif (preg_match('/OPR\/([0-9\.]+)/', $userAgent, $matches) || preg_match('/Opera\/([0-9\.]+)/', $userAgent, $matches)) {
                $browserName = 'Opera';
                $browserVersion = $matches[1];
            }
            // Firefox
            elseif (preg_match('/Firefox\/([0-9\.]+)/', $userAgent, $matches)) {
                $browserName = 'Firefox';
                $browserVersion = $matches[1];
            }
            // Safari
            elseif (preg_match('/Safari\/([0-9\.]+)/', $userAgent, $matches) && !preg_match('/Chrome|Chromium/', $userAgent)) {
                $browserName = 'Safari';
                if (preg_match('/Version\/([0-9\.]+)/', $userAgent, $versionMatches)) {
                    $browserVersion = $versionMatches[1];
                } else {
                    $browserVersion = $matches[1];
                }
            }
            // Chrome
            elseif (preg_match('/Chrome\/([0-9\.]+)/', $userAgent, $matches)) {
                $browserName = 'Chrome';
                $browserVersion = $matches[1];
            }
            // Internet Explorer
            elseif (preg_match('/MSIE\s([0-9\.]+)/', $userAgent, $matches) || preg_match('/Trident.*rv:([0-9\.]+)/', $userAgent, $matches)) {
                $browserName = 'Internet Explorer';
                $browserVersion = $matches[1];
            }
            
            // Détection du système d'exploitation
            
            // Windows
            if (preg_match('/Windows NT\s?([0-9\.]+)/', $userAgent, $matches)) {
                $osName = 'Windows';
                $ntVersion = $matches[1];
                
                // Mapping des versions NT aux noms commerciaux de Windows
                $windowsVersions = [
                    '10.0' => '10/11',
                    '6.3' => '8.1',
                    '6.2' => '8',
                    '6.1' => '7',
                    '6.0' => 'Vista',
                    '5.2' => 'Server 2003/XP x64',
                    '5.1' => 'XP',
                    '5.0' => '2000'
                ];
                
                $osVersion = isset($windowsVersions[$ntVersion]) ? $windowsVersions[$ntVersion] : $ntVersion;
                
                // Détecter Windows 11 basé sur Edge
                if ($osVersion === '10/11' && $browserName === 'Microsoft Edge') {
                    $osVersion = '11';
                } else if ($osVersion === '10/11') {
                    $osVersion = '10';
                }
                
                $deviceBrand = 'PC';
                $deviceModel = 'Windows PC';
            }
            // macOS / OS X
            elseif (preg_match('/Mac OS X\s?([0-9_\.]+)/', $userAgent, $matches)) {
                $osName = 'macOS';
                $osVersion = str_replace('_', '.', $matches[1]);
                $deviceBrand = 'Apple';
                $deviceModel = 'Mac';
            }
            // iOS
            elseif (preg_match('/(iPhone|iPad|iPod).*OS\s([0-9_]+)/', $userAgent, $matches)) {
                $osName = 'iOS';
                $osVersion = str_replace('_', '.', $matches[2]);
                $deviceBrand = 'Apple';
                $deviceModel = $matches[1];
            }
            // Android
            elseif (preg_match('/Android\s([0-9\.]+)/', $userAgent, $matches)) {
                $osName = 'Android';
                $osVersion = $matches[1];
                $deviceBrand = 'Android Device';
                
                // Extraction de la marque et du modèle d'appareil sur Android
                if (preg_match('/;\s*([^;]+)\s+Build\//', $userAgent, $deviceMatches)) {
                    $deviceInfo = trim($deviceMatches[1]);
                    $parts = explode(' ', $deviceInfo, 2);
                    $deviceBrand = $parts[0];
                    $deviceModel = isset($parts[1]) ? $parts[1] : $deviceBrand;
                }
            }
            // Linux
            elseif (preg_match('/Linux/', $userAgent)) {
                $osName = 'Linux';
                
                // Détecter les distributions spécifiques
                if (preg_match('/Ubuntu/', $userAgent)) {
                    $osName = 'Ubuntu';
                }
                
                $deviceBrand = 'PC';
                $deviceModel = 'Linux PC';
            }
            
            // Définition des valeurs détectées
            $auditLog->setDeviceBrand($deviceBrand);
            $auditLog->setDeviceModel($deviceModel);
            $auditLog->setOsName($osName);
            $auditLog->setOsVersion($osVersion);
            $auditLog->setBrowserName($browserName);
            $auditLog->setBrowserVersion($browserVersion);
            
        } catch (\Throwable $e) {
            $auditLog->setDeviceBrand('Unknown');
            $auditLog->setDeviceModel('Unknown');
            $auditLog->setOsName('Unknown');
            $auditLog->setOsVersion('Unknown');
            $auditLog->setBrowserName('Unknown');
            $auditLog->setBrowserVersion('Unknown');
        }
    }

    private function displayResults(SymfonyStyle $io, AuditLog $auditLog): void
    {
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Système d\'exploitation', $auditLog->getOsName() . ' ' . $auditLog->getOsVersion()],
                ['Navigateur', $auditLog->getBrowserName() . ' ' . $auditLog->getBrowserVersion()],
                ['Appareil', $auditLog->getDeviceBrand() . ' ' . $auditLog->getDeviceModel()],
            ]
        );
    }
}