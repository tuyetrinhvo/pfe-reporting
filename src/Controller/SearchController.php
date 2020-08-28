<?php

namespace App\Controller;

use App\Entity\Search;
use App\Service\ReportingCsvExporter;
use App\Service\ReportingDataBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SearchController
 * @package App\Controller
 */
class SearchController extends AbstractController
{
    /**
     * @param array $issues
     * @param string $name
     * @param object $form
     * @return Response
     * @throws \Exception
     */
    public function createSearch($issues, $name, $form)
    {
        $issuesToString = json_encode($issues);
        $entityManager = $this->getDoctrine()->getManager();

        // find and delete data whose search date is less than last year
        $searchOfOneYearAgo = $this->getDoctrine()
            ->getRepository(Search::class)
            ->findSearchByDateOnYearAgo();
        if (!empty($searchOfOneYearAgo)) {
            foreach ($searchOfOneYearAgo as $searchForDelete) {
                $entityManager->remove($searchForDelete);
            }
        }

        // check if the name exists to create or update data
        $checkSearchName = $this->getDoctrine()
            ->getRepository(Search::class)
            ->findBy(['name' => $name]);

        if (empty($checkSearchName)) {
            $newSearch = new Search();
            $newSearch->setName($name);
            $newSearch->setIssues($issuesToString);
            $entityManager->persist($newSearch);
            $entityManager->flush();
            $search = $this->getDoctrine()
                ->getRepository(Search::class)
                ->find($newSearch->getId());
        } else {
            $search = $this->getDoctrine()
                ->getRepository(Search::class)
                ->find($checkSearchName[0]->getId());
            $search->setIssues($issuesToString);
            $search->setDate(new \DateTime());
            $entityManager->flush();
        }

        // remove customDataReporting session which is saved in ReportingController
        $this->get('session')->remove('customDataReporting');

        return $this->render('reporting/reporting.html.twig', [
            'form' => $form->createView(),
            'search' => $search
        ]);
    }

    /**
     *
     * @Route("search/show-all", name="list_searches")
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function showAllSearchesAction(Request $request, PaginatorInterface $paginator)
    {
        $allOfSearches = $this->getDoctrine()
            ->getRepository(Search::class)
            ->findBy([], ['date' => 'desc']);

        if (empty($allOfSearches)) {
            return $this->showError('Aucune recherche sauvegardée !');
        }

        $searchesWithPagination = $paginator->paginate(
            $allOfSearches,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('search/show.html.twig', [
            'searches' => $searchesWithPagination
        ]);
    }

    /**
     *
     * @Route("search/delete/{id}", name="delete_search")
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function deleteSearchAction(int $id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $search = $entityManager->getRepository(Search::class)->find($id);

        if ($search !== null) {
            $entityManager->remove($search);
            $entityManager->flush();
        }

        return $this->redirectToRoute('list_searches');
    }

    /**
     *
     * @Route("search/export-reporting/{id}", name="export_search_reporting")
     *
     * @param int $id
     * @param ReportingCsvExporter $csvExporter
     * @return Response
     */
    public function exportReportingOfSearchAction(int $id, ReportingCsvExporter $csvExporter)
    {
        $dataOfOneSearch = $this->getIssuesOfSearch($id);

        if (array_key_exists('error', $dataOfOneSearch)) {
            return $this->showError($dataOfOneSearch['error']);
        }

        return $csvExporter->exportCsvAction($dataOfOneSearch["issues"], $dataOfOneSearch["nameOfPeriod"]);
    }

    /**
     *
     * @Route("search/reporting/{id}", name="show_search_reporting")
     *
     * @param int $id
     * @param ReportingDataBuilder $dataBuilder
     * @return Response
     */
    public function showSearchReportingAction(int $id, ReportingDataBuilder $dataBuilder)
    {
        $dataOfOneSearch = $this->getIssuesOfSearch($id);

        if (array_key_exists('error', $dataOfOneSearch)) {
            return $this->showError($dataOfOneSearch['error']);
        }

        $reporting = $dataBuilder->getDataForInfos( $dataOfOneSearch['issues']);
        $charts = $dataBuilder->getCharts($dataOfOneSearch['issues']);

        // data to build one or more charts is incorrect or missing, then all charts cannot be displayed
        foreach ($charts as $value) {
            if ($value === null) {
                $charts = null;
            }
        }

        $allOfSearches = $this->getDoctrine()
            ->getRepository(Search::class)
            ->find($id);

        return $this->render('search/show.html.twig', [
            'search' => $allOfSearches,
            'name_of_period' => $dataOfOneSearch['nameOfPeriod'],
            'reporting' => $reporting,
            'charts' => $charts
        ]);
    }

    /**
     *
     * @Route("search/all-issues/{id}", name="search_all_issues")
     *
     * @param int $id
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function showAllIssuesOfOneSearchAction(int $id, Request $request, PaginatorInterface $paginator)
    {
        $dataOfOneSearch = $this->getIssuesOfSearch($id);

        if (array_key_exists('error', $dataOfOneSearch)) {
            return $this->showError($dataOfOneSearch['error']);
        }

        $allIssuesWithPagination = $paginator->paginate(
            $dataOfOneSearch['issues'],
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('reporting/all.html.twig', [
            'issues' => $allIssuesWithPagination,
            'period' => $dataOfOneSearch['nameOfPeriod']
        ]);
    }

    /**
     * @param int $id
     * @return array
     */
    private function getIssuesOfSearch(int $id)
    {
        $search = $this->getDoctrine()
            ->getRepository(Search::class)
            ->find($id);

        if ($search === null) {
            return ['error' => 'Période de recherche id ' . $id . ' n\'existe pas !'];
        }

        $issues = json_decode($search->getIssues(), true);
        $nameOfPeriod = $search->getName();

        return ["issues" => $issues, "nameOfPeriod" => $nameOfPeriod];
    }

    /**
     * @param string $error
     * @return Response
     */
    private function showError($error)
    {
        return $this->render('search/error.html.twig', ['error' => $error]);
    }

    /**
     * This function is reserved for quick check all issues of one search with Postman
     *
     * @Route("api/search/reporting/{id}", name="api_search_reporting", methods={"GET"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function allIssuesOfOneSearchApiAction(int $id)
    {
        if (array_key_exists('error', $this->getIssuesOfSearch($id))) {
            return new JsonResponse($this->getIssuesOfSearch($id), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($this->getIssuesOfSearch($id)['issues'], Response::HTTP_OK);
    }

}
