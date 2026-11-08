<?php
/*
 * Created on   : Sun Nov 08 2026
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileHandlerInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Interfaces\FileServices;

use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;

/**
 * Gemeinsame Basis von FileServices und PreProcessServices.
 */
interface FileHandlerInterface {
    public static function getPattern(): string;

    /**
     * Reihenfolge, in der der FileDispatcher die Services prüft: höhere Werte zuerst,
     * bei gleicher Priorität alphabetisch. Generische Fallbacks bekommen negative Werte.
     */
    public static function getPriority(): int;

    /**
     * @param array<array-key, string> $matches
     * @param-out array<array-key, string> $matches
     */
    public static function matchesPattern(string $file, array &$matches = []): bool;

    public function getFile(): string;
    public function getFilename(): string;

    public function getClient(): ?Client;
    public function getDocument(): ?Document;
}
