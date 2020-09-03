<?php

namespace App\Service;


use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ReportingCsvExporter
 * @package App\Service
 */
class ReportingCsvExporter
{
    /**
     *
     * @param $issues
     * @param $nameOfPeriod
     * @return StreamedResponse
     */
    public function exportCsvAction($issues, $nameOfPeriod): StreamedResponse
    {
        $response = new StreamedResponse();

        $response->setCallback(function () use ($issues) {

            $handle = fopen('php://output', 'w');

            $headers = ['Numéro', 'Priorité', 'Type de tracker', 'Plateforme', 'Crée le', 'Fermé le', 'Temps passé', 'Lien'];

            fputcsv($handle, $headers, ';', '"');

            foreach ($issues as $issue) {
                fputcsv($handle, $issue, ';', '"');
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_periode_' . $nameOfPeriod . '.csv"');

        return $response;
    }
}