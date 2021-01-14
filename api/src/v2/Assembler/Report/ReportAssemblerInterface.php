<?php

namespace App\v2\Assembler\Report;

use App\v2\DTO\ReportDto;

interface ReportAssemblerInterface
{
    /**
     * @param array $data
     * @return ReportDto
     */
    public function assembleFromArray(array $data);
}