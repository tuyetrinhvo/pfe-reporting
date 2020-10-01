<?php

namespace App\Controller;


use App\Form\PeriodReportingType;
use App\Service\ReportingCsvExporter;
use App\Service\ReportingDataBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ReportingController
 * @package App\Controller
 */
class ReportingController extends AbstractController
{
    /**
     *
     * @Route("/", name="reporting")
     *
     * @param Request $request
     * @param ReportingCsvExporter $csvExporter
     * @param ReportingDataBuilder $dataBuilder
     * @return Response
     * @throws \Exception
     */
    public function searchByPeriodFormAction(Request $request, ReportingCsvExporter $csvExporter, ReportingDataBuilder $dataBuilder)
    {
        $form = $this->createForm(PeriodReportingType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // set format date for redmineManager
            $data = $dataBuilder->setFormatDate($form->getData());

            // check if all fields are not empty
            if ($dataBuilder->checkArrayIfOnlyNull($data) ||
                (($data['period'] === "one day" || $data['period'] === "custom_range") &&
                    empty($data['begin']) && empty($data['end']))) {
                $this->addFlash('error', 'Merci de renseigner une période ou une date');

                return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);
            }

            $dateBegin = date('d/m/Y', strtotime($data['begin']));
            $dateEnd = date('d/m/Y', strtotime($data['end']));

            // check if the interval is correct
            if ($data['end'] != null && $data['begin'] > $data['end']) {
                $this->addFlash('error',
                    'Du ' . $dateBegin . ' au ' . $dateEnd . ' : Période choisie incorrecte !');

                return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);
            }

            // set the name of the research period
            $period = $data['begin'] && $data['end']
                ? $dateBegin . ' et ' . $dateEnd
                : ($data['begin'] ? $dateBegin : $dateEnd);

            $nameOfPeriod = $data['choice'] ? 'tickets créés le ' . $period : 'tickets fermés le ' . $period;

            $issues = $dataBuilder->getIssues($data);

            // check if there are issues
            if (empty($issues)) {
                $this->addFlash('error', 'Pas d\'issue pour la période choisie');

                return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);
            }

            $dataInSession = $this->get('session')->get('searchTime');

            // reset session 'searchTime' after 1 hour
            $searchTimeoneHourLater = (new \DateTime($dataInSession))
                ->modify('+1 hour')
                ->format('Y-m-d H:i:s');

            $now = (new \DateTime())->format('Y-m-d H:i:s');

            if (empty($dataInSession) || $searchTimeoneHourLater <= $now) {
                $this->get('session')->set('searchTime', $now);
                $this->forward('App\Controller\IssueController:createIssue');
            }

            $reporting = $dataBuilder->getDataForInfos($issues);
            $charts = $dataBuilder->getCharts($issues);

            // data to build one or more charts is incorrect or missing, then all charts cannot be displayed
            foreach ($charts as $value) {
                if ($value === null) {
                    $charts = null;
                }
            }

            // actions for the different submits
            if ($form->get('export')->isClicked()) {
                return $csvExporter->exportCsvAction($issues, $nameOfPeriod);

            } elseif ($form->get('report')->isClicked()) {
                return $this->render('reporting/reporting.html.twig', [
                    'form' => $form->createView(),
                    'name_of_period' => $nameOfPeriod,
                    'reporting' => $reporting,
                    'charts' => $charts
                ]);

            } elseif ($form->get('saveSearch')->isClicked()) {
                return $this->forward('App\Controller\SearchController:createSearch', [
                    'issues' => $issues,
                    'name' => $nameOfPeriod,
                    'form' => $form
                ]);
            }

        }

        return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);

    }

}