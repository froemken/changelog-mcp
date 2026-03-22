<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Reaction;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

/**
 * This reaction listens for MCP request.
 * It searches prepared TYPO3 changelog files.
 * And returns more context-related information.
 */
readonly class ChangelogMcpReaction implements ReactionInterface
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public static function getType(): string
    {
        return 'changelog-mcp';
    }

    public static function getDescription(): string
    {
        return 'changelog_mcp.reaction:description';
    }

    public static function getIconIdentifier(): string
    {
        return 'module-install-environment';
    }

    public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string)json_encode(['foo' => 'bar'])));
    }
}
