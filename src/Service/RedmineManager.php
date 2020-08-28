<?php

namespace App\Service;


use Redmine\Client;

/**
 * Class RedmineManager
 * @package App\Service
 */
class RedmineManager
{
    /**
     * @var Client
     */
    private $client;

    /**
     * RedmineManager constructor.
     */
    public function __construct()
    {
        $this->client = new Client('http://redmine.example.com', '1234567890exemple');
    }

    /**
     * @param array $array
     * @return bool
     */
    public function checkArrayIfOnlyNull($array)
    {
        return empty(array_filter($array, static function ($value) {
            return $value !== null;
        }));
    }

    /**
     * @param array $data
     * @return array
     */
    public function getIssuesForCreatedOn($data)
    {
        $created_on = $this->findPeriods($data);
        return $this->client->issue->all([
            'offset' => 0,
            'limit' => 5000,
            'sort' => 'created_on',
            'project_id' => 54,
            'status_id' => '*',
            'created_on' => $created_on,
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    public function getIssuesForClosedOn($data)
    {
        $closed_on = $this->findPeriods($data);
        return $this->client->issue->all([
            'offset' => 0,
            'limit' => 5000,
            'sort' => 'closed_on',
            'project_id' => 54,
            'status_id' => 'closed',
            'closed_on' => $closed_on,
        ]);
    }

    /**
     * This function is reserved to build array type of chart in ReportingDataBuilder
     *
     * @param string $name
     * @return array
     */
    public function getValuesOfCustomFieldByName($name)
    {
        $allCustomFields = $this->client->custom_fields->all();
        $values = [];
        foreach ($allCustomFields['custom_fields'] as $customFields) {
            if ($customFields['name'] === $name) {
                if (isset($customFields['possible_values'])) {
                    foreach ($customFields['possible_values'] as $possibleValues) {
                        $values[] = $possibleValues['value'];
                    }
                }

            }
        }
        return $values;
    }

    /**
     * @param array $data
     * @return string
     */
    private function findPeriods($data)
    {
        $period = '';

        if ($data['begin'] !== null && $data['end'] === null) {
            $period = $data['begin'];

        } elseif ($data['end'] !== null && $data['begin'] === null) {
            $period = $data['end'];

        } elseif ($data['begin'] !== null && $data['end'] !== null) {
            $period = "><" . $data['begin'] . "|" . $data['end'];
        }

        return $period;
    }

    /**
     * @param array $issue
     * @return array
     */
    public function setCustomFields($issue)
    {
        $category = $this->getCustomFieldById($issue['custom_fields'], 28)['value'];

        if ($category == '') {
            $category = 'non défini';
        }
        $typeTraqueur = $this->getCustomFieldById($issue['custom_fields'], 69)['value'];
        $plateforme = $this->getCustomFieldById($issue['custom_fields'], 70)['value'];
        $timeEntries = $this->findTimeSpend($issue['id']);

        $timeSpend = 0;
        if (is_array($timeEntries['time_entries']) === true) {
            foreach ($timeEntries['time_entries'] as $timeEntry) {
                $timeSpend += $timeEntry['hours'];
            }
        }

        return $this->setData($issue, $category, $typeTraqueur, $plateforme, $timeSpend);
    }

    /**
     * @param array $issue
     * @param string $category
     * @param string $typeTraqueur
     * @param string $plateforme
     * @param int $timeSpend
     * @return array
     */
    private function setData($issue, $category, $typeTraqueur, $plateforme, $timeSpend)
    {
        if (isset($issue['closed_on'])) {
            $closed_on =
                date('d/m/Y H:i:s', strtotime(trim(str_replace(['T', 'Z'], ' ', $issue['closed_on']))));
        } else {
            $closed_on = '';
        }

        if (isset($issue['created_on'])) {
            $created_on =
                date('d/m/Y H:i:s', strtotime(trim(str_replace(['T', 'Z'], ' ', $issue['created_on']))));
        } else {
            $created_on = '';
        }

        if (isset($issue['assigned_to']['name'])) {
            $assigned_to = $issue['assigned_to']['name'];
        } else {
            $assigned_to = '';
        }

        return array(
            'Numéro' => $issue['id'],
            'Sujet' => $issue['subject'],
            'Tracker' => $issue['tracker']['name'],
            'Catégorie' => $category,
            'Priorité' => $issue['priority']['name'],
            'Auteur' => $issue['author']['name'],
            'Type de traqueur' => $typeTraqueur,
            'Plateforme' => $plateforme,
            'Assigné à' => $assigned_to,
            'Créé le' => $created_on,
            'Fermé le' => $closed_on,
            'Temps passé' => $timeSpend,
            'Lien' => 'http://redmine.example.com/issues/' . $issue['id'], //exemple
        );
    }

    /**
     * @param array $customFields
     * @param int $id
     * @return mixed|null
     */
    private function getCustomFieldById($customFields, $id)
    {
        $customField = null;

        foreach ($customFields as $field) {
            if ($field['id'] == $id) {
                $customField = $field;
            }
        }
        return $customField;
    }

    /**
     * @param int $issue_id
     * @return array
     */
    private function findTimeSpend($issue_id)
    {
        return $this->client->time_entry->all([
            'issue_id' => $issue_id
        ]);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function setFormatDate($data)
    {
        switch ($data['period']) {

            case 'custom_range':
                $data['begin'] = $data['begin'] != null ? $data['begin']->format('Y-m-d') : null;
                $data['end'] = $data['end'] != null ? $data['end']->format('Y-m-d') : null;
                break;

            case 'yesterday':
                $data['begin'] = date('Y-m-d', strtotime('-1 days'));
                $data['end'] = null;
                break;
            case 'this_week':
                $data['begin'] = date('Y-m-d', strtotime('monday this week'));
                $data['end'] = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'last_week':
                $data['begin'] = date('Y-m-d', strtotime('monday last week'));
                $data['end'] = date('Y-m-d', strtotime('sunday last week'));
                break;
            case 'this_month':
                $data['begin'] = date('Y-m-d', strtotime('first day of this month'));
                $data['end'] = date('Y-m-d', strtotime('last day of this month'));
                break;
            case 'last_month':
                $data['begin'] = date('Y-m-d', strtotime('first day of last month'));
                $data['end'] = date('Y-m-d', strtotime('last day of last month'));
                break;
            case 'last_three_months':
                $data['begin'] = date('Y-m-d', strtotime('-3 months'));
                $data['end'] = date('Y-m-d', strtotime('now'));
                break;
            case 'last_six_months':
                $data['begin'] = date('Y-m-d', strtotime('-6 months'));
                $data['end'] = date('Y-m-d', strtotime('now'));
                break;
            case 'last_year':
                $data['begin'] = date('Y-m-d', strtotime('first day of january last year'));
                $data['end'] = date('Y-m-d', strtotime('last day of december last year'));
                break;
            default: // 'today'
                $data['begin'] = date('Y-m-d');
                $data['end'] = null;

        }

        return $data;
    }

}