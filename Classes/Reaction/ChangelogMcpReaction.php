<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Reaction;

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

/**
 * This reaction listens for MCP request.
 * It searches prepared TYPO3 changelog files.
 * And returns more context-related information.
 *
 * This class must not be marked as readonly to remain compatible with PHP 8.2.
 * The TYPO3 Reactions extension registers all ReactionInterface implementations
 * with setLazy(true) via registerForAutoconfiguration in its Services.php.
 * PHP 8.2 does not support generating lazy proxies by inheriting from readonly
 * classes, causing a fatal error during container compilation.
 *
 * @noinspection PhpClassCanBeReadonlyInspection
 */
class ChangelogMcpReaction implements ReactionInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
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

    public function react(
        ServerRequestInterface $request,
        array $payload,
        ReactionInstruction $reaction,
    ): ResponseInterface {
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/changelog_mcp_sessions');

        $server = Server::builder()
            ->setServerInfo('TYPO3 Changelog MCP Server', '0.0.1')
            // Set the protocol version to be compatible with PhpStorm MCP integration
            ->setProtocolVersion(ProtocolVersion::V2024_11_05)
            ->setDiscovery(
                basePath: GeneralUtility::getFileAbsFileName('EXT:changelog_mcp/Classes/'),
                scanDirs: ['Tool'],
            )
            ->setSession(
                new Server\Session\FileSessionStore(
                    Environment::getVarPath() . '/changelog_mcp_sessions',
                ),
            )
            ->build();

        // Ensure the request body stream is at the beginning for the transport to read it.
        // This is a common issue with PSR-7 streams that might have been read by other middlewares.
        $request->getBody()->rewind();

        /** @var NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        $detectedHost = $normalizedParams->getHttpHost();

        $middleware = [
            new Server\Transport\Http\Middleware\ProtocolVersionMiddleware(),
            new Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware([
                $detectedHost,
                'localhost',
                '127.0.0.1',
            ]),
            new Server\Transport\Http\Middleware\CorsMiddleware(
                allowedOrigins: ['*'],
                allowedMethods: ['GET', 'POST', 'OPTIONS'],
                allowedHeaders: [
                    'Accept',
                    'Authorization',
                    'Content-Type',
                    'Last-Event-ID',
                    Server\Transport\StreamableHttpTransport::PROTOCOL_VERSION_HEADER,
                    Server\Transport\StreamableHttpTransport::SESSION_HEADER,
                ]
            ),
        ];

        $transport = new Server\Transport\StreamableHttpTransport(
            request: $request,
            logger: $this->logger,
            middleware: $middleware,
        );

        return $server->run($transport);
    }
}
