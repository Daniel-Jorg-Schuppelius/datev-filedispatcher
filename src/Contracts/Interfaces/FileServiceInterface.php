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

namespace App\Contracts\Interfaces;

use Datev\Entities\ClientMasterData\Clients\Client;

interface FileServiceInterface {
    public static function getPattern(): string;
    public static function matchesPattern(string $filename, array &$matches = null): bool;

    public function process(): void;

    public function getFilename(): string;
    public function getClient(): ?Client;
}
