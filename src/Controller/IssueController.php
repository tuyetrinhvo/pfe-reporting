<?php

namespace App\Controller;

use App\Entity\Issue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IssueController extends AbstractController
{
    /**
     *
     * @throws \Exception
     */
    public function createIssue(): Response
    {
        $random = rand(0, 24);

        if ($random % 10 === 0) {
            $type = 'Bug';
            $platform = 'Console';
            $priority = 'Urgent';
        } elseif ($random % 9 === 0) {
            $type = 'Configuration';
            $platform = 'Publish';
            $priority = 'Normal';
        } elseif ($random % 8 === 0) {
            $type = 'Evolution';
            $platform = 'Publish';
            $priority = 'Normal';
        } elseif ($random % 7 === 0) {
            $type = 'Configuration';
            $platform = 'Perf';
            $priority = 'Normal';
        } elseif ($random % 6 === 0) {
            $type = 'Erreur utilisateur';
            $platform = 'Support';
            $priority = 'Immédiat';
        } elseif ($random % 5 === 0) {
            $type = 'Plateforme Externe';
            $platform = 'Publish';
            $priority = 'Haut';
        } elseif ($random % 4 === 0) {
            $type = 'Traffic';
            $platform = 'Console';
            $priority = 'Haut';
        } elseif ($random % 3 === 0) {
            $type = 'Export';
            $platform = 'Native';
            $priority = 'Normal';
        } elseif ($random % 2 === 0) {
            $type = 'RGPD';
            $platform = 'Support';
            $priority = 'Bas';
        } else {
            $type = 'Autre';
            $platform = 'Wordpress';
            $priority = 'Bas';
        }

        $lastIssue = $this->getDoctrine()
            ->getRepository(Issue::class)
            ->findLastIssueById();

        $number = $lastIssue->getNumber() + 1;

        $link = 'https://redmine.example.com/issues/' . $number;

        $timeRandom = rand(10, 17) . ':' . rand(10, 59) . ':' . rand(10, 59);

        if((new \DateTime())->format('N') > 5) {
            $create = new \DateTime('-4 days ' . $timeRandom);
            $close = new \DateTime('-3 days ' . $timeRandom);
        } else {
            $create = new \DateTime('-3 days ' . $timeRandom);
            $close = new \DateTime('today ' . $timeRandom);
        }

        $timeSpent = round(($random / rand(1, 10)), 2);

        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to the action: createIssue(EntityManagerInterface $entityManager)
        $entityManager = $this->getDoctrine()->getManager();

        $issue = new Issue();
        $issue->setNumber($number);
        $issue->setPriority($priority);
        $issue->setType($type);
        $issue->setPlatform($platform);
        $issue->setClosedOn($close);
        $issue->setCreatedOn($create);
        $issue->setTimeSpent($timeSpent);
        $issue->setLink($link);

        // tell Doctrine you want to (eventually) save the Issue (no queries yet)
        $entityManager->persist($issue);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->redirectToRoute('reporting');

    }

}
