<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : FileServiceInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Interfaces\FileServices;

use Datev\Entities\ClientMasterData\Clients\Client;
use Datev\Entities\DocumentManagement\Documents\Document;

interface PreProcessFileServiceInterface {
    public static function getPattern(): string;
    public static function matchesPattern(string $file, array &$matches = null): bool;

    public function preProcess(): bool;

    public function getFile(): string;
    public function getFilename(): string;

    public function getClient(): ?Client;
    public function getDocument(): ?Document;
}
