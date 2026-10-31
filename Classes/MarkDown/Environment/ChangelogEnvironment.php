<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Environment;

use Doctrine\RST\Environment;
use Doctrine\RST\References\Reference;
use Doctrine\RST\References\ResolvedReference;
use StefanFroemken\ChangelogMcp\MarkDown\Reference\GenericReference;

class ChangelogEnvironment extends Environment
{
    /**
     * @var array<string, Reference>
     */
    private array $registeredReferences = [];

    public function registerReference(Reference $reference): void
    {
        $this->registeredReferences[$reference->getName()] = $reference;
        parent::registerReference($reference);
    }

    public function resolve(string $section, string $data): ?ResolvedReference
    {
        if (!isset($this->registeredReferences[$section])) {
            $this->registerReference(new GenericReference($section));
        }

        return parent::resolve($section, $data);
    }

    /**
     * @return string[]|null
     */
    public function found(string $section, string $data): ?array
    {
        if (!isset($this->registeredReferences[$section])) {
            $this->registerReference(new GenericReference($section));
        }

        return parent::found($section, $data);
    }
}
