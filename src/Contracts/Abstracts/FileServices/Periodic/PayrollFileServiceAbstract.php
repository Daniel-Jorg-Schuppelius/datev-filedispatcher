<?php
/*
 * Created on   : Tue Oct 22 2024
 * Author       : Daniel Jörg Schuppelius
 * Author Uri   : https://schuppelius.org
 * Filename     : PayrollFileServiceAbstract.php
 * License      : MIT License
 * License Uri  : https://opensource.org/license/mit
 */

declare(strict_types=1);

namespace App\Contracts\Abstracts\FileServices\Periodic;

use App\Contracts\Abstracts\FileServices\PeriodicFileServiceAbstract;
use RuntimeException;

abstract class PayrollFileServiceAbstract extends PeriodicFileServiceAbstract {
    protected const SUBFOLDER = "02 Entgeltabrechnung";

    protected function extractDataFromFile(): void {
        $matches = $this->getMatches();

        if (array_key_exists("tenant", $matches)) {
            try {
                $this->setClients($matches["tenant"]);
            } catch (RuntimeException $e) {
                if (is_null($this->payrollClient)) {
                    $this->setPayrollClient($matches["tenant"]);
                }

                if (is_null($this->client) && !is_null($this->payrollClient)) {
                    $this->client = $this->clientsEndpoint->get($this->payrollClient->getID());
                    if (is_null($this->client)) {
                        self::$logger->error("Client konnte nicht aus den Payrolldaten ermittelt werden: " . $matches["tenant"]);
                        throw $e;
                    }

                    self::$logger->notice("Client wurde aus den Payrolldaten ermittelt: " . $this->payrollClient->getNumber() . " -> " . $this->client->getNumber());
                }
            }
        }

        if (array_key_exists("year", $matches) && array_key_exists("month", $matches)) {
            $this->setDate((int) $matches["year"], (int) $matches["month"]);
        }
    }
}
