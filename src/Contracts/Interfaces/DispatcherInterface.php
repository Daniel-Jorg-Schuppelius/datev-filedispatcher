<?php
/*
 * Created on   : Mon Oct 07 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : DispatcherInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Interfaces;

interface DispatcherInterface {
    public function __construct(string $filename, ?string $destinationFolder = null);

    public function getDestinationFolder(): string;
    public function getFilename(): string;

    public function setDestinationFolder(string $destinationFolder): void;
    public function setFilename(string $filename): void;
}
