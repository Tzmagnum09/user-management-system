<?php

namespace App\Service;

use WhichBrowser\Parser as WhichBrowser;
use Detection\MobileDetect;
use UAParser\Parser as UAParser;
use Psr\Log\LoggerInterface;

class BrowserDetectionService
{
    private LoggerInterface $logger;
    private MobileDetect $mobileDetect;
    private UAParser $uaParser;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->mobileDetect = new MobileDetect();
        $this->uaParser = UAParser::create();
    }

    /**
     * Analyze user agent with multiple detection libraries
     */
    public function analyzeUserAgent(string $userAgent): array
    {
        $result = [
            'device_brand' => 'Unknown',
            'device_model' => 'Unknown',
            'os_name' => 'Unknown',
            'os_version' => 'Unknown',
            'browser_name' => 'Unknown',
            'browser_version' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true,
            'user_agent' => $userAgent
        ];

        // Log raw user agent for debugging
        $this->logger->debug('Analyzing user agent', ['user_agent' => $userAgent]);

        try {
            // 1. First try with WhichBrowser
            $whichBrowserResult = $this->analyzeWithWhichBrowser($userAgent);
            
            // 2. Then with UAParser
            $uaParserResult = $this->analyzeWithUAParser($userAgent);
            
            // 3. Finally with MobileDetect
            $mobileDetectResult = $this->analyzeWithMobileDetect($userAgent);
            
            // Combine results with priority: WhichBrowser > UAParser > MobileDetect > Manual fallback
            $result = $this->combineResults($whichBrowserResult, $uaParserResult, $mobileDetectResult, $userAgent);
            
            // Log combined detection result
            $this->logger->debug('Detection result', [
                'device' => $result['device_brand'] . ' ' . $result['device_model'],
                'os' => $result['os_name'] . ' ' . $result['os_version'],
                'browser' => $result['browser_name'] . ' ' . $result['browser_version'],
                'is_mobile' => $result['is_mobile'] ? 'Yes' : 'No',
                'is_tablet' => $result['is_tablet'] ? 'Yes' : 'No',
                'is_desktop' => $result['is_desktop'] ? 'Yes' : 'No'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error analyzing user agent', [
                'error' => $e->getMessage(),
                'user_agent' => $userAgent
            ]);
            
            // If all parsers fail, use manual detection as fallback
            $result = $this->manualDetection($userAgent);
        }

        return $result;
    }

    /**
     * Analyze user agent with WhichBrowser
     */
    private function analyzeWithWhichBrowser(string $userAgent): array
    {
        $result = [
            'device_brand' => 'Unknown',
            'device_model' => 'Unknown',
            'os_name' => 'Unknown',
            'os_version' => 'Unknown',
            'browser_name' => 'Unknown',
            'browser_version' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true
        ];

        try {
            $parser = new WhichBrowser(['headers' => ['User-Agent' => $userAgent]]);
            
            // Device information
            if ($parser->device->manufacturer) {
                $result['device_brand'] = $parser->device->manufacturer;
            }
            
            if ($parser->device->model) {
                $result['device_model'] = $parser->device->model;
            }
            
            if ($parser->device->type === 'mobile') {
                $result['is_mobile'] = true;
                $result['is_desktop'] = false;
            } elseif ($parser->device->type === 'tablet') {
                $result['is_tablet'] = true;
                $result['is_desktop'] = false;
            }
            
            // OS information
            if ($parser->os->name) {
                $result['os_name'] = $parser->os->name;
            }
            
            if ($parser->os->version) {
                $result['os_version'] = $parser->os->version->toString();
            }
            
            // Browser information
            if ($parser->browser->name) {
                $result['browser_name'] = $parser->browser->name;
            }
            
            if ($parser->browser->version) {
                $result['browser_version'] = $parser->browser->version->toString();
            }
            
            $this->logger->debug('WhichBrowser detection result', $result);
            
        } catch (\Exception $e) {
            $this->logger->warning('WhichBrowser detection failed', [
                'error' => $e->getMessage(),
                'user_agent' => $userAgent
            ]);
        }

        return $result;
    }

    /**
     * Analyze user agent with UAParser
     */
    private function analyzeWithUAParser(string $userAgent): array
    {
        $result = [
            'device_brand' => 'Unknown',
            'device_model' => 'Unknown',
            'os_name' => 'Unknown',
            'os_version' => 'Unknown',
            'browser_name' => 'Unknown',
            'browser_version' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true
        ];

        try {
            $parsed = $this->uaParser->parse($userAgent);
            
            // Device information
            if ($parsed->device->brand && $parsed->device->brand !== 'Generic') {
                $result['device_brand'] = $parsed->device->brand;
            }
            
            if ($parsed->device->model && $parsed->device->model !== 'Smartphone') {
                $result['device_model'] = $parsed->device->model;
            }
            
            // OS information
            if ($parsed->os->family && $parsed->os->family !== 'Other') {
                $result['os_name'] = $parsed->os->family;
            }
            
            if ($parsed->os->toVersion()) {
                $result['os_version'] = $parsed->os->toVersion();
            }
            
            // Browser information
            if ($parsed->ua->family && $parsed->ua->family !== 'Other') {
                $result['browser_name'] = $parsed->ua->family;
            }
            
            if ($parsed->ua->toVersion()) {
                $result['browser_version'] = $parsed->ua->toVersion();
            }
            
            // Mobile detection based on OS or device
            if (preg_match('/(iPhone|iPod|iPad|Android|BlackBerry|Windows Phone)/i', $userAgent)) {
                // Determine if it's mobile or tablet
                if (preg_match('/(iPad|Android(?!.*Mobile))/i', $userAgent)) {
                    $result['is_tablet'] = true;
                    $result['is_mobile'] = false;
                    $result['is_desktop'] = false;
                } else {
                    $result['is_mobile'] = true;
                    $result['is_tablet'] = false;
                    $result['is_desktop'] = false;
                }
            }
            
            $this->logger->debug('UAParser detection result', $result);
            
        } catch (\Exception $e) {
            $this->logger->warning('UAParser detection failed', [
                'error' => $e->getMessage(),
                'user_agent' => $userAgent
            ]);
        }

        return $result;
    }

    /**
     * Analyze user agent with MobileDetect
     */
    private function analyzeWithMobileDetect(string $userAgent): array
    {
        $result = [
            'device_brand' => 'Unknown',
            'device_model' => 'Unknown',
            'os_name' => 'Unknown',
            'os_version' => 'Unknown',
            'browser_name' => 'Unknown',
            'browser_version' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true
        ];

        try {
            // Set user agent for MobileDetect
            $_SERVER['HTTP_USER_AGENT'] = $userAgent;
            $this->mobileDetect->setUserAgent($userAgent);
            
            // Check device type
            $result['is_mobile'] = $this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet();
            $result['is_tablet'] = $this->mobileDetect->isTablet();
            $result['is_desktop'] = !$this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet();
            
            // Detect OS
            if ($this->mobileDetect->is('iOS')) {
                $result['os_name'] = 'iOS';
                
                // Try to extract iOS version
                if (preg_match('/OS\s+([\d_]+)/i', $userAgent, $matches)) {
                    $result['os_version'] = str_replace('_', '.', $matches[1]);
                }
            } elseif ($this->mobileDetect->is('AndroidOS')) {
                $result['os_name'] = 'Android';
                
                // Try to extract Android version
                if (preg_match('/Android\s+([\d\.]+)/i', $userAgent, $matches)) {
                    $result['os_version'] = $matches[1];
                }
            } elseif (strpos($userAgent, 'Windows') !== false) {
                $result['os_name'] = 'Windows';
                
                // Extract Windows version
                if (preg_match('/Windows NT\s+([\d\.]+)/i', $userAgent, $matches)) {
                    $winVersionMap = [
                        '10.0' => '10',
                        '6.3' => '8.1',
                        '6.2' => '8',
                        '6.1' => '7',
                        '6.0' => 'Vista',
                        '5.2' => 'Server 2003/XP x64',
                        '5.1' => 'XP',
                        '5.0' => '2000'
                    ];
                    $result['os_version'] = $winVersionMap[$matches[1]] ?? $matches[1];
                }
            } elseif (strpos($userAgent, 'Mac OS X') !== false) {
                $result['os_name'] = 'macOS';
                
                // Extract macOS version
                if (preg_match('/Mac OS X\s+([\d_\.]+)/i', $userAgent, $matches)) {
                    $result['os_version'] = str_replace('_', '.', $matches[1]);
                }
            } elseif (strpos($userAgent, 'Linux') !== false) {
                $result['os_name'] = 'Linux';
            }
            
            // Detect device brands based on rules
            foreach ([
                'iPhone' => 'Apple',
                'iPad' => 'Apple',
                'iPod' => 'Apple',
                'Samsung' => 'Samsung',
                'Huawei' => 'Huawei',
                'Xiaomi' => 'Xiaomi',
                'OPPO' => 'OPPO',
                'Vivo' => 'Vivo',
                'Pixel' => 'Google',
                'OnePlus' => 'OnePlus',
                'LG' => 'LG',
                'Sony' => 'Sony',
                'Nokia' => 'Nokia',
                'Motorola' => 'Motorola',
                'HTC' => 'HTC'
            ] as $keyword => $brand) {
                if (stripos($userAgent, $keyword) !== false) {
                    $result['device_brand'] = $brand;
                    
                    // Try to extract model
                    if ($brand === 'Apple') {
                        if (stripos($userAgent, 'iPhone') !== false) {
                            $result['device_model'] = 'iPhone';
                        } elseif (stripos($userAgent, 'iPad') !== false) {
                            $result['device_model'] = 'iPad';
                        } elseif (stripos($userAgent, 'iPod') !== false) {
                            $result['device_model'] = 'iPod';
                        } elseif (stripos($userAgent, 'Mac') !== false) {
                            $result['device_model'] = 'Mac';
                        }
                    } elseif ($brand === 'Google' && preg_match('/Pixel\s+(\d+)/i', $userAgent, $matches)) {
                        $result['device_model'] = 'Pixel ' . $matches[1];
                    } elseif ($brand === 'Samsung' && preg_match('/SM-([A-Z0-9]+)/i', $userAgent, $matches)) {
                        $result['device_model'] = 'SM-' . $matches[1];
                    } else {
                        // Generic model detection
                        preg_match('/' . preg_quote($keyword, '/') . '\s+([A-Z0-9][A-Z0-9\-_]*)/i', $userAgent, $matches);
                        if (!empty($matches[1])) {
                            $result['device_model'] = $matches[1];
                        }
                    }
                    
                    break;
                }
            }
            
            // Detect browser
            if ($this->mobileDetect->is('Chrome')) {
                $result['browser_name'] = 'Chrome';
                if (preg_match('/Chrome\/([\d\.]+)/i', $userAgent, $matches)) {
                    $result['browser_version'] = $matches[1];
                }
            } elseif (preg_match('/(Edge|Edg)\/([\d\.]+)/i', $userAgent, $matches)) {
                $result['browser_name'] = 'Edge';
                $result['browser_version'] = $matches[2];
            } elseif ($this->mobileDetect->is('Safari') && !$this->mobileDetect->is('Chrome')) {
                $result['browser_name'] = 'Safari';
                if (preg_match('/Version\/([\d\.]+)/i', $userAgent, $matches)) {
                    $result['browser_version'] = $matches[1];
                }
            } elseif ($this->mobileDetect->is('Firefox')) {
                $result['browser_name'] = 'Firefox';
                if (preg_match('/Firefox\/([\d\.]+)/i', $userAgent, $matches)) {
                    $result['browser_version'] = $matches[1];
                }
            } elseif (preg_match('/(OPR|Opera)\/([\d\.]+)/i', $userAgent, $matches)) {
                $result['browser_name'] = 'Opera';
                $result['browser_version'] = $matches[2];
            } elseif (preg_match('/(MSIE|Trident\/.*rv:)\s*([\d\.]+)/i', $userAgent, $matches)) {
                $result['browser_name'] = 'Internet Explorer';
                $result['browser_version'] = $matches[2];
            }
            
            $this->logger->debug('MobileDetect detection result', $result);
            
        } catch (\Exception $e) {
            $this->logger->warning('MobileDetect detection failed', [
                'error' => $e->getMessage(),
                'user_agent' => $userAgent
            ]);
        }

        return $result;
    }

    /**
     * Combine results from multiple parsers with priority system
     */
    private function combineResults(array $whichBrowser, array $uaParser, array $mobileDetect, string $userAgent): array
    {
        $result = [
            'device_brand' => 'Unknown',
            'device_model' => 'Unknown',
            'os_name' => 'Unknown',
            'os_version' => 'Unknown',
            'browser_name' => 'Unknown',
            'browser_version' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true,
            'user_agent' => $userAgent
        ];
        
        // Device brand detection priority: WhichBrowser > UAParser > MobileDetect
        if ($whichBrowser['device_brand'] !== 'Unknown') {
            $result['device_brand'] = $whichBrowser['device_brand'];
        } elseif ($uaParser['device_brand'] !== 'Unknown') {
            $result['device_brand'] = $uaParser['device_brand'];
        } elseif ($mobileDetect['device_brand'] !== 'Unknown') {
            $result['device_brand'] = $mobileDetect['device_brand'];
        }
        
        // Device model detection priority: WhichBrowser > UAParser > MobileDetect
        if ($whichBrowser['device_model'] !== 'Unknown') {
            $result['device_model'] = $whichBrowser['device_model'];
        } elseif ($uaParser['device_model'] !== 'Unknown') {
            $result['device_model'] = $uaParser['device_model'];
        } elseif ($mobileDetect['device_model'] !== 'Unknown') {
            $result['device_model'] = $mobileDetect['device_model'];
        }
        
        // Special handling for PC/Desktop devices
        if ($result['device_brand'] === 'Unknown' && !$mobileDetect['is_mobile'] && !$mobileDetect['is_tablet']) {
            if (strpos($userAgent, 'Windows') !== false) {
                $result['device_brand'] = 'PC';
                $result['device_model'] = 'Windows PC';
            } elseif (strpos($userAgent, 'Mac') !== false) {
                $result['device_brand'] = 'Apple';
                $result['device_model'] = 'Mac';
            } elseif (strpos($userAgent, 'Linux') !== false) {
                $result['device_brand'] = 'PC';
                $result['device_model'] = 'Linux PC';
            }
        }
        
        // OS name detection priority: WhichBrowser > MobileDetect > UAParser
        if ($whichBrowser['os_name'] !== 'Unknown') {
            $result['os_name'] = $whichBrowser['os_name'];
        } elseif ($mobileDetect['os_name'] !== 'Unknown') {
            $result['os_name'] = $mobileDetect['os_name'];
        } elseif ($uaParser['os_name'] !== 'Unknown') {
            $result['os_name'] = $uaParser['os_name'];
        }
        
        // OS version detection priority: WhichBrowser > MobileDetect > UAParser
        if ($whichBrowser['os_version'] !== 'Unknown') {
            $result['os_version'] = $whichBrowser['os_version'];
        } elseif ($mobileDetect['os_version'] !== 'Unknown') {
            $result['os_version'] = $mobileDetect['os_version'];
        } elseif ($uaParser['os_version'] !== 'Unknown') {
            $result['os_version'] = $uaParser['os_version'];
        }
        
        // Browser name detection priority: WhichBrowser > MobileDetect > UAParser
        if ($whichBrowser['browser_name'] !== 'Unknown') {
            $result['browser_name'] = $whichBrowser['browser_name'];
        } elseif ($mobileDetect['browser_name'] !== 'Unknown') {
            $result['browser_name'] = $mobileDetect['browser_name'];
        } elseif ($uaParser['browser_name'] !== 'Unknown') {
            $result['browser_name'] = $uaParser['browser_name'];
        }
        
        // Browser version detection priority: WhichBrowser > MobileDetect > UAParser
        if ($whichBrowser['browser_version'] !== 'Unknown') {
            $result['browser_version'] = $whichBrowser['browser_version'];
        } elseif ($mobileDetect['browser_version'] !== 'Unknown') {
            $result['browser_version'] = $mobileDetect['browser_version'];
        } elseif ($uaParser['browser_version'] !== 'Unknown') {
            $result['browser_version'] = $uaParser['browser_version'];
        }
        
        // Device type detection - aggregate from all sources, with priority
        if ($whichBrowser['is_mobile'] || $mobileDetect['is_mobile'] || $uaParser['is_mobile']) {
            $result['is_mobile'] = true;
            $result['is_desktop'] = false;
            $result['is_tablet'] = false;
        } elseif ($whichBrowser['is_tablet'] || $mobileDetect['is_tablet'] || $uaParser['is_tablet']) {
            $result['is_tablet'] = true;
            $result['is_desktop'] = false;
            $result['is_mobile'] = false;
        } else {
            $result['is_desktop'] = true;
            $result['is_mobile'] = false;
            $result['is_tablet'] = false;
        }
        
        return $result;
    }

    /**
     * Manual detection as fallback
     */
    private function manualDetection(string $userAgent): array
    {
        $result = [
            'device_brand' => 'Unknown',
            'device_model' => 'Unknown',
            'os_name' => 'Unknown',
            'os_version' => 'Unknown',
            'browser_name' => 'Unknown',
            'browser_version' => 'Unknown',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true,
            'user_agent' => $userAgent
        ];

        // Windows detection
        if (preg_match('/(Windows NT (\d+\.\d+))/i', $userAgent, $winMatches)) {
            $result['os_name'] = 'Windows';
            $winVersionMap = [
                '10.0' => '10',
                '6.3' => '8.1',
                '6.2' => '8',
                '6.1' => '7',
                '6.0' => 'Vista',
                '5.2' => 'Server 2003/XP x64',
                '5.1' => 'XP',
                '5.0' => '2000'
            ];
            $result['os_version'] = $winVersionMap[$winMatches[2]] ?? $winMatches[2];
            $result['device_brand'] = 'PC';
            $result['device_model'] = 'Windows PC';
        }
        
        // macOS detection
        elseif (preg_match('/Mac OS X (\d+[._]\d+[._]?\d*)/i', $userAgent, $macMatches)) {
            $result['os_name'] = 'macOS';
            $result['os_version'] = str_replace('_', '.', $macMatches[1]);
            $result['device_brand'] = 'Apple';
            $result['device_model'] = 'Mac';
        }
        
        // iOS detection
        elseif (preg_match('/(iPhone|iPad|iPod)/i', $userAgent, $iosDeviceMatches)) {
            $result['os_name'] = 'iOS';
            $result['device_brand'] = 'Apple';
            $result['device_model'] = $iosDeviceMatches[1];
            
            if (preg_match('/OS (\d+[._]\d+[._]?\d*)/i', $userAgent, $iosMatches)) {
                $result['os_version'] = str_replace('_', '.', $iosMatches[1]);
            }
            
            $result['is_desktop'] = false;
            if ($iosDeviceMatches[1] === 'iPad') {
                $result['is_tablet'] = true;
            } else {
                $result['is_mobile'] = true;
            }
        }
        
        // Android detection
        elseif (preg_match('/Android (\d+(\.\d+)*)/i', $userAgent, $androidMatches)) {
            $result['os_name'] = 'Android';
            $result['os_version'] = $androidMatches[1];
            $result['is_desktop'] = false;
            
            // Check if it's a tablet
            if (strpos($userAgent, 'Mobile') === false) {
                $result['is_tablet'] = true;
            } else {
                $result['is_mobile'] = true;
            }
            
            // Try to detect brand and model
            if (preg_match('/SM-([A-Z0-9]+)/i', $userAgent, $samsungMatches)) {
                $result['device_brand'] = 'Samsung';
                $result['device_model'] = 'SM-' . $samsungMatches[1];
            } elseif (strpos($userAgent, 'Pixel') !== false && preg_match('/Pixel (\d+)/i', $userAgent, $pixelMatches)) {
                $result['device_brand'] = 'Google';
                $result['device_model'] = 'Pixel ' . $pixelMatches[1];
            } elseif (strpos($userAgent, 'Xiaomi') !== false || strpos($userAgent, 'MI ') !== false || strpos($userAgent, 'Redmi') !== false) {
                $result['device_brand'] = 'Xiaomi';
                if (preg_match('/(Redmi|MI) ([A-Z0-9]+)/i', $userAgent, $xiaomiMatches)) {
                    $result['device_model'] = $xiaomiMatches[1] . ' ' . $xiaomiMatches[2];
                }
            } elseif (strpos($userAgent, 'Huawei') !== false) {
                $result['device_brand'] = 'Huawei';
                if (preg_match('/Huawei ([A-Z0-9\-]+)/i', $userAgent, $huaweiMatches)) {
                    $result['device_model'] = $huaweiMatches[1];
                }
            }
        }
        
        // Linux detection
        elseif (preg_match('/Linux/i', $userAgent)) {
            if (preg_match('/Ubuntu/i', $userAgent)) {
                $result['os_name'] = 'Ubuntu';
            } elseif (preg_match('/Debian/i', $userAgent)) {
                $result['os_name'] = 'Debian';
            } elseif (preg_match('/Fedora/i', $userAgent)) {
                $result['os_name'] = 'Fedora';
            } else {
                $result['os_name'] = 'Linux';
            }
            
            if (preg_match('/x86_64/i', $userAgent)) {
                $result['os_version'] = 'x86_64';
            } elseif (preg_match('/i686/i', $userAgent)) {
                $result['os_version'] = 'i686';
            }
            
            $result['device_brand'] = 'PC';
            $result['device_model'] = 'Linux PC';
        }
        
        // Browser detection
        
        // Chrome
        if (preg_match('/Chrome\/(\d+\.\d+)/i', $userAgent, $chromeMatches) && 
            !preg_match('/(Edge|Edg|OPR|Opera|SamsungBrowser|UCBrowser|YaBrowser)/i', $userAgent)) {
            $result['browser_name'] = 'Chrome';
            $result['browser_version'] = $chromeMatches[1];
        }
        
        // Edge
        elseif (preg_match('/(Edge|Edg)\/(\d+\.\d+)/i', $userAgent, $edgeMatches)) {
            $result['browser_name'] = 'Edge';
            $result['browser_version'] = $edgeMatches[2];
        }
        
        // Firefox
        elseif (preg_match('/Firefox\/(\d+\.\d+)/i', $userAgent, $ffMatches)) {
            $result['browser_name'] = 'Firefox';
            $result['browser_version'] = $ffMatches[1];
        }
        
        // Safari
        elseif (preg_match('/Safari\/(\d+\.\d+)/i', $userAgent, $safariMatches) && 
                !preg_match('/Chrome|Chromium/i', $userAgent)) {
            $result['browser_name'] = 'Safari';
            if (preg_match('/Version\/(\d+\.\d+)/i', $userAgent, $versionMatches)) {
                $result['browser_version'] = $versionMatches[1];
            } else {
                $result['browser_version'] = $safariMatches[1];
            }
        }
        
        // Opera
        elseif (preg_match('/(OPR|Opera)\/(\d+\.\d+)/i', $userAgent, $operaMatches)) {
            $result['browser_name'] = 'Opera';
            $result['browser_version'] = $operaMatches[2];
        }
        
        // IE
        elseif (preg_match('/(MSIE |Trident\/.*rv:)(\d+\.\d+)/i', $userAgent, $ieMatches)) {
            $result['browser_name'] = 'Internet Explorer';
            $result['browser_version'] = $ieMatches[2];
        }

        $this->logger->debug('Manual detection result', $result);
        
        return $result;
    }
}