<?php

/**
 * Reactions CLI — maintenance commands for the MODX 3 extra.
 *
 * Usage (from MODX site root):
 *   php core/components/reactions/cli.php <command> [subcommand] [options]
 *
 * Commands:
 *   recount [--class-key=modResource] [--object-id=5] [--context=web]
 *   cleanup [--orphans]
 *   export [--class-key=] [--object-id=] [--context=] [--file=path.json]
 *   type create --name=favorite [--emoji=⭐] [--ordering=0]
 *   type list
 *   type remove --id=1 | --name=favorite
 *   set create --key=updown [--title="Up/Down"] [--types=like,dislike]
 *   set list
 *   set attach --key=updown --types=like,dislike [--replace]
 *   set remove --id=1 | --key=updown
 *   ban add (--ip=1.2.3.4 | --user=12) [--reason=spam] [--days=7 | --expires=timestamp]
 *   ban remove --id=1 | --ip=1.2.3.4 | --user=12
 *   ban list
 *   stats [--limit=10]
 *
 * Examples:
 *   php core/components/reactions/cli.php recount
 *   php core/components/reactions/cli.php recount --class-key=modResource --object-id=5
 *   php core/components/reactions/cli.php type create --name=favorite --emoji=⭐
 *   php core/components/reactions/cli.php export --file=/tmp/reactions.json
 *   php core/components/reactions/cli.php cleanup --orphans
 */

declare(strict_types=1);

use Reactions\Console\AbstractCommand;
use Reactions\Console\BanCommand;
use Reactions\Console\CleanupCommand;
use Reactions\Console\ExportCommand;
use Reactions\Console\RecountCommand;
use Reactions\Console\SetCommand;
use Reactions\Console\StatsCommand;
use Reactions\Console\TypeCommand;
use Reactions\Reactions;

define('MODX_API_MODE', true);

$componentPath = __DIR__ . '/';
$modxRoot = resolveReactionsModxRoot($argv[0] ?? '');

if ($modxRoot === null) {
    fwrite(STDERR, "MODX index.php not found. Run from the site root, e.g.:\n  php core/components/reactions/cli.php <command>\n");
    exit(1);
}

require $modxRoot . '/index.php';

/** @var \MODX\Revolution\modX $modx */
$modx->initialize('mgr');

$vendorAutoload = $componentPath . 'vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

$modx->addPackage('Reactions\\Model\\', $componentPath . 'src/', null, 'Reactions\\');

$reactions = new Reactions($modx);

[$command, $subcommand, $options] = parseCliArguments(array_slice($argv, 1));

if ($subcommand !== '') {
    $options['action'] = $subcommand;
}

$map = [
    'recount' => RecountCommand::class,
    'cleanup' => CleanupCommand::class,
    'export' => ExportCommand::class,
    'type' => TypeCommand::class,
    'set' => SetCommand::class,
    'ban' => BanCommand::class,
    'stats' => StatsCommand::class,
];

if ($command === '' || !isset($map[$command])) {
    printUsage($map);
    exit($command === '' || $command === 'help' ? 0 : 1);
}

/** @var class-string<AbstractCommand> $class */
$class = $map[$command];
/** @var AbstractCommand $handler */
$handler = new $class($modx, $reactions, $options);

exit($handler->execute());

/**
 * @return array{0: string, 1: string, 2: array<string, mixed>}
 */
function parseCliArguments(array $args): array
{
    $command = '';
    $subcommand = '';
    $options = [];

    foreach ($args as $arg) {
        if (!is_string($arg)) {
            continue;
        }

        if (str_starts_with($arg, '--')) {
            $options = array_merge($options, parseOptionToken($arg));
            continue;
        }

        if ($command === '') {
            $command = strtolower($arg);
            continue;
        }

        if ($subcommand === '') {
            $subcommand = strtolower($arg);
        }
    }

    return [$command, $subcommand, $options];
}

/**
 * @return array<string, mixed>
 */
function parseOptionToken(string $token): array
{
    $body = substr($token, 2);

    if ($body === '') {
        return [];
    }

    if (!str_contains($body, '=')) {
        return [$body => true];
    }

    [$name, $value] = explode('=', $body, 2);
    $name = strtolower($name);

    if ($value === 'true') {
        return [$name => true];
    }

    if ($value === 'false') {
        return [$name => false];
    }

    return [$name => $value];
}

/**
 * Resolve MODX root when the component path is a symlink into Extras/.
 * Prefer the invocation path (`$argv[0]` + cwd), then walk parents of cwd / __DIR__.
 */
function resolveReactionsModxRoot(string $argv0): ?string
{
    $candidates = [];

    if ($argv0 !== '') {
        $invoked = $argv0;
        if (!str_starts_with($invoked, DIRECTORY_SEPARATOR)) {
            $invoked = getcwd() . DIRECTORY_SEPARATOR . $invoked;
        }
        $candidates[] = dirname(normalizeCliPath($invoked), 4);
    }

    $candidates[] = getcwd();
    $candidates[] = dirname(__DIR__, 3);
    $candidates[] = dirname(__DIR__, 4);

    $dir = getcwd();
    for ($i = 0; $i < 8; ++$i) {
        $candidates[] = $dir;
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    foreach ($candidates as $root) {
        if (!is_string($root) || $root === '') {
            continue;
        }
        $root = rtrim(normalizeCliPath($root), DIRECTORY_SEPARATOR);
        if (is_file($root . '/index.php') && is_file($root . '/core/config/config.inc.php')) {
            return $root;
        }
    }

    return null;
}

/**
 * Collapse `.` / `..` segments without resolving symlinks.
 */
function normalizeCliPath(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $parts = [];
    foreach (explode('/', $path) as $i => $segment) {
        if ($segment === '' && $i > 0) {
            continue;
        }
        if ($segment === '.') {
            continue;
        }
        if ($segment === '..') {
            if ($parts !== [] && end($parts) !== '..' && end($parts) !== '') {
                array_pop($parts);
                continue;
            }
        }
        $parts[] = $segment;
    }

    $normalized = implode('/', $parts);

    return $normalized === '' ? '/' : $normalized;
}

/**
 * @param array<string, class-string<AbstractCommand>> $map
 */
function printUsage(array $map): void
{
    $commands = implode(', ', array_keys($map));

    fwrite(STDOUT, <<<TEXT
Reactions CLI

Usage:
  php core/components/reactions/cli.php <command> [subcommand] [options]

Commands: {$commands}

Run with a command name to execute. See file header in cli.php for examples.

TEXT);
}
