<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PreProcessFileServiceInterface.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Interfaces\FileServices;

use App\Enums\PreProcessResult;

interface PreProcessFileServiceInterface extends FileHandlerInterface {
    public function preProcess(): PreProcessResult;
}
