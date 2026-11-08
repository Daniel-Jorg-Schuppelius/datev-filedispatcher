<?php
/*
 * Created on   : Tue Oct 08 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PreProcessFileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices;

use App\Contracts\Interfaces\FileServices\PreProcessFileServiceInterface;
use App\Enums\PreProcessResult;

abstract class PreProcessFileServiceAbstract extends FileHandlerAbstract implements PreProcessFileServiceInterface {
    abstract public function preProcess(): PreProcessResult;
}
