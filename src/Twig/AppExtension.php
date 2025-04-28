<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getParameter', [$this, 'getParameter']),
        ];
    }

    public function getParameter(string $name)
    {
        return $this->parameterBag->get($name, null);
    }

    public function getName(): string
    {
        return 'app_extension';
    }
}