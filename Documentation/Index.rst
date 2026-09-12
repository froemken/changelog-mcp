..  include:: /Includes.rst.txt

..  _start:

========================
MCP for TYPO3 changelogs
========================

:Extension key:
    changelog_mcp

:Package name:
    stefanfroemken/changelog-mcp

:Version:
    0.0.3

:Language:
    en

:Author:
    Stefan Froemken & contributors

:License:
    GPL-2.0-or-later

The `changelog_mcp` extension parses official TYPO3 Core changelogs, stores them
in the database, and exposes them via the Model Context Protocol (MCP). It allows
local language models, AI assistants, and IDEs to query exact TYPO3 Core
deprecations, breaking changes, features, and migration steps directly from your
TYPO3 installation.

..  toctree::
    :maxdepth: 2
    :titlesonly:
    :hidden:

    Introduction/Index
    Installation/Index
    Configuration/Index
    Usage/Index
    Developer/Index
    KnownProblems/Index
    ChangeLog/Index

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Introduction <introduction>`

        Learn about the Model Context Protocol in TYPO3, supported features,
        and architecture goals.

    ..  card:: :ref:`Installation <installation>`

        Prerequisites, Composer commands, database migration, and running the
        changelog preparation indexer.

    ..  card:: :ref:`Configuration <configuration>`

        Configure TYPO3 Reactions for HTTP transport, API authentication keys,
        and session storage.

    ..  card:: :ref:`Usage & Integration <usage>`

        STDIO and HTTP transports, curl examples, and MCP client configurations
        for Claude Desktop, Cursor, and Windsurf.

    ..  card:: :ref:`Developer Guide <developer>`

        Internal architecture, AST parser transformation, Twig templates, and
        weighted search mechanics.

    ..  card:: :ref:`Known Problems & FAQ <known-problems>`

        Common setup questions, permission troubleshooting, and issue reporting.

    ..  card:: :ref:`ChangeLog <changelog>`

        Curated release history and version tracking.
