<?php declare(strict_types=1);


namespace AppBundle\Service\Csv;

class SatisfactionCsvGenerator
{
    /**
     * @var CsvBuilder
     */
    private CsvBuilder $csvBuilder;

    public function __construct(CsvBuilder $csvBuilder)
    {
        $this->csvBuilder = $csvBuilder;
    }

    public function generateSatisfactionResponsesCsv(array $satisfactions)
    {
        $headers = ['Satisfaction Score', 'Comments', 'Deputy Role', 'Report Type', 'Date Provided'];
        $rows = [];

        foreach ($satisfactions as $satisfaction) {
            $rows[] = [
                $satisfaction->getScore(),
                $satisfaction->getComments(),
                $satisfaction->getDeputyrole(),
                $satisfaction->getReporttype(),
                $satisfaction->getCreated()->format('Y-m-d')
            ];
        }

        return $this->csvBuilder->buildCsv($headers, $rows);
    }
}
