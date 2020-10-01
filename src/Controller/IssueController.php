<?php

namespace App\Controller;

use App\Entity\Issue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IssueController extends AbstractController
{
    /**
     * @Route("issue", name="issue")
     * @throws \Exception
     */
    public function createIssue(): Response
    {
        $typeArray = [
            'Autre', 'Bug', 'Evolution', 'Erreur utilisateur', 'Export', 'Configuration', 'Plateforme Externe', 'RGPD', 'Traffic'
        ];
        $platformArray = ['Console', 'Native', 'Perf', 'Publish', 'Support', 'Wordpress'];
        $priorityArray = ['Bas', 'Normal', 'Haut', 'Urgent', 'Immédiat'];

        $lastIssue = $this->getDoctrine()
            ->getRepository(Issue::class)
            ->findLastIssueById();

        $number = $lastIssue->getNumber() + 1;

        $link = 'https://redmine.example.com/issues/' . $number;

        $timeRandom = rand(10, 17) . ':' . rand(10, 59) . ':' . rand(10, 59);

        if ((new \DateTime())->format('N') > 5) {
            $create = new \DateTime('-3 days ' . $timeRandom);
        } else {
            $create = new \DateTime('today ' . $timeRandom);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $issue = new Issue();
        $issue->setNumber($number);
        $issue->setPriority($priorityArray[array_rand($priorityArray)]);
        $issue->setType($typeArray[array_rand($typeArray)]);
        $issue->setPlatform($platformArray[array_rand($platformArray)]);
        $issue->setCreatedOn($create);
        $issue->setTimeSpent(0);
        $issue->setLink($link);
        $issue->setStatut('créé');

        // tell Doctrine you want to (eventually) save the Issue (no queries yet)
        $entityManager->persist($issue);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        $this->updateIssues();

        return $this->redirectToRoute('reporting');

    }

    /**
     * @throws \Exception
     */
    private function updateIssues()
    {
        $checkClosedOnIssues = $this->getDoctrine()
            ->getRepository(Issue::class)
            ->findBy(['closedOn' => null]);

        if ($checkClosedOnIssues) {

            $timeRandom = rand(10, 17) . ':' . rand(10, 59) . ':' . rand(10, 59);

            foreach ($checkClosedOnIssues as $item) {
                if ($item->getCreatedOn() < new \DateTime('-1 week')) {

                    if ($item->getPriority() == 'Immédiat' || $item->getPriority() == 'Urgent' || $item->getTimeSpent() > 14) {
                        $statut = 'fermé';

                        if ((new \DateTime())->format('N') > 5) {
                            $item->setClosedOn(new \DateTime('-3 days ' . $timeRandom));
                        } else {
                            $item->setClosedOn(new \DateTime('today ' . $timeRandom));
                        }
                    } else {
                        $statut = 'en_cours';
                    }

                    $item->setStatut($statut);
                    $item->setTimeSpent($item->getTimeSpent() + (round((rand(0, 8) / rand(1, 10)), 2)));
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }
    }

}
