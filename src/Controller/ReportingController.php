<?php

namespace App\Controller;


use App\Form\PeriodReportingType;
use App\Service\ReportingCsvExporter;
use App\Service\RedmineManager;
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
     * @var RedmineManager
     */
    private $redmineManager;

    /**
     * ReportingController constructor.
     */
    public function __construct()
    {
        $this->redmineManager = new RedmineManager();
    }

    /**
     *
     * @Route("reporting", name="reporting")
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
            $data = $this->redmineManager->setFormatDate($form->getData());

            // check if all fields are not empty
            if ($this->redmineManager->checkArrayIfOnlyNull($data) ||
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

            $nameOfPeriod = $data['choice'] ? 'créé le ' . $period : 'fermé le ' . $period;
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            // prepare customData array with nameOfPeriod for defined session condition
            $customData = ['nameOfPeriod' => $nameOfPeriod, 'dateTime' => $now];

            // store session 'customDataReporting' into $dataInSession
            $dataInSession = $this->get('session')->get('customDataReporting');

            // reset session 'customDataReporting' after 1 hour
            $dateTimePlusOneHour = (new \DateTime($dataInSession['dateTime']))
                ->modify('+1 hour')
                ->format('Y-m-d H:i:s');

            // store data of the last search in session to save time for others submit actions
            if (empty($dataInSession) || $dataInSession['nameOfPeriod'] !== $nameOfPeriod || $dateTimePlusOneHour <= $now) {
                // Get issues with created_on or closed_on
                $issuesArray = $data['choice']
                    ? $this->redmineManager->getIssuesForCreatedOn($data)
                    : $this->redmineManager->getIssuesForClosedOn($data);

                // errors come from an incorrect date format
                if (array_key_exists('errors', $issuesArray)) {
                    foreach ($issuesArray['errors'] as $error) {
                        $this->addFlash('error', 'Date ' . $error);
                    }

                    return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);
                }

                // check if there are issues
                if (empty($issuesArray['issues'])) {
                    $this->addFlash('error', 'Pas d\'issue pour la période choisie');

                    return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);
                }

                // build data with custom fields
                foreach ($issuesArray['issues'] as $issue) {
                    $customData[] = $this->redmineManager->setCustomFields($issue);
                }

                $this->get('session')->set('customDataReporting', $customData);
                $dataInSession = $this->get('session')->get('customDataReporting');

            }

            unset($dataInSession['nameOfPeriod'], $dataInSession['dateTime']);

            $reporting = $dataBuilder->getDataForInfos($dataInSession);
            $charts = $dataBuilder->getCharts($dataInSession);

            // data to build one or more charts is incorrect or missing, then all charts cannot be displayed
            foreach ($charts as $value) {
                if ($value === null) {
                    $charts = null;
                }
            }

            // actions for the different submits
            if ($form->get('export')->isClicked()) {
                return $csvExporter->exportCsvAction($dataInSession, $nameOfPeriod);

            } elseif ($form->get('report')->isClicked()) {
                return $this->render('reporting/reporting.html.twig', [
                    'form' => $form->createView(),
                    'name_of_period' => $nameOfPeriod,
                    'reporting' => $reporting,
                    'charts' => $charts
                ]);

            } elseif ($form->get('saveSearch')->isClicked()) {
                return $this->forward('App\Controller\SearchController:createSearch', [
                    'issues' => $dataInSession,
                    'name' => $nameOfPeriod,
                    'form' => $form
                ]);
            }

        }

        return $this->render('reporting/reporting.html.twig', ['form' => $form->createView()]);

    }

}