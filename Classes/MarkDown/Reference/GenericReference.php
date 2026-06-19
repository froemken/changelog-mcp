<?php
 
declare(strict_types=1);
 
/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
 
namespace StefanFroemken\ChangelogMcp\MarkDown\Reference;
 
use Doctrine\RST\Environment;
use Doctrine\RST\References\Reference;
use Doctrine\RST\References\ResolvedReference;
 
class GenericReference extends Reference
{
    public function __construct(
        private readonly string $name,
    ) {}
 
    public function getName(): string
    {
        return $this->name;
    }
 
    public function resolve(Environment $environment, string $data): ?ResolvedReference
    {
        if ($this->name === 'issue') {
            return new ResolvedReference(
                null,
                $data,
                'https://forge.typo3.org/issues/' . $data,
                [],
                ['role' => $this->name]
            );
        }
 
        return new ResolvedReference(
            null,
            $data,
            null,
            [],
            ['role' => $this->name]
        );
    }
}
