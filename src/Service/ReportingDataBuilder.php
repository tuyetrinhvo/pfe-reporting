<?php

namespace App\Service;

use App\Entity\Issue;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ReportingDataBuilder
 * @package App\Service
 */
class ReportingDataBuilder
{
    private const TYPE = 'Type';
    private const PLATFORM = 'Platform';
    private const CHARTS_COLORS_PRIO = ['#7effdd', '#307771', '#ffcb3b', '#ef723d', '#c72539'];
    private const CHARTS_HAXIS_PRIO = ['Nombre', 'Bas', 'Normal', 'Haut', 'Urgent', 'Immédiat'];
    private const CHARTS_HAXIS_HOUR = ['Nombre', 'heures'];
    private const CHARTS_HAXIS_TICKET = ['Nombre', 'tickets'];
    private const CHARTS_VAXIS_HOUR = 'Nombre d\'heures';
    private const CHARTS_VAXIS_TICKET = 'Nombre de tickets';
    private const ARRAY_PLATFORMS = ['Console', 'Native', 'Perf', 'Publish', 'Support', 'Wordpress'];
    private const ARRAY_TYPE_TRACKER = [
        'Autre', 'Bug', 'Evolution', 'Erreur utilisateur', 'Export', 'Configuration', 'Plateforme Externe', 'RGPD', 'Traffic'
    ];
    private $entityManager;

    /**
     * ReportingDataBuilder constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @param array $issues
     * @return array
     */
    public function getDataForInfos($issues)
    {
        $nbIssues = count($issues);

        $totalTimeSpend = 0;
        $issuesTimeSpendUndefined = [];

        foreach ($issues as $issue) {
            if ($issue['timeSpent'] > 0) {
                $totalTimeSpend += $issue['timeSpent'];
            } elseif ($issue['timeSpent'] == 0) {
                $issuesTimeSpendUndefined[] = $issue['link'];
            }

        }

        $nbIssuesTimeSpendUndefined = count($issuesTimeSpendUndefined);

        return [
            'nbIssues' => $nbIssues,
            'totalTimeSpend' => $totalTimeSpend,
            'issuesTimeUndefined' => $issuesTimeSpendUndefined,
            'nbIssuesTimeUndefined' => $nbIssuesTimeSpendUndefined
        ];
    }

    /**
     * @param array $issues
     * @return array
     */
    public function getCharts($issues)
    {
        $titleHoursTracker = 'Temps passés par type de tracker';
        $titleHoursPlatform = 'Temps passés par plateforme';
        $titleTicketsTracker = 'Nombre de tickets par type de tracker';
        $titleTicketsPlatform = 'Nombre de tickets par plateforme';

        $charts = [
            'ticketsTrackerPrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleTicketsTracker,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            ),
            'ticketsPlatformPrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATFORM, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleTicketsPlatform,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            ),
            'ticketsTracker' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE),
                self::CHARTS_HAXIS_TICKET,
                [
                    'title' => $titleTicketsTracker,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'gridlines' => 10,
                    'legend' => 'none',
                    'colors' => ['#ffc844']
                ]
            ),
            'ticketsPlatform' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATFORM),
                self::CHARTS_HAXIS_TICKET,
                [
                    'title' => $titleTicketsPlatform,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'gridlines' => 10,
                    'legend' => 'none',
                    'colors' => ['#65b2e8']
                ]
            ),
            'hoursTracker' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE, false, true),
                self::CHARTS_HAXIS_HOUR,
                [
                    'title' => $titleHoursTracker,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'logScale' => true,
                    'scaleType' => 'mirrorLog',
                    'legend' => 'none',
                    'colors' => ['#ffc844']
                ]
            ),
            'hoursPlatform' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATFORM, false, true),
                self::CHARTS_HAXIS_HOUR,
                [
                    'title' => $titleHoursPlatform,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'logScale' => true,
                    'scaleType' => 'mirrorLog',
                    'legend' => 'none',
                    'colors' => ['#65b2e8']
                ]
            ),
            'hoursTrackerPrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE, true, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleHoursTracker,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            ),
            'hoursPlatformPrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATFORM, true, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleHoursPlatform,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            )
        ];

        return array_merge($charts, $this->allChartsPlatform($issues));
    }

    /**
     * @param $issues
     * @param $platform
     * @return ColumnChart|void
     */
    private function chartsEachPlatformHour($issues, $platform)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE, false, true, $platform),
            self::CHARTS_HAXIS_HOUR,
            [
                'title' => 'Temps passés sur ' . $platform,
                'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                'logScale' => true,
                'scaleType' => 'mirrorLog',
                'legend' => 'none',
                'colors' => ['#ffc844']
            ]
        );
    }

    /**
     * @param $issues
     * @param $platform
     * @return ColumnChart|void
     */
    private function chartsEachPlatformTicket($issues, $platform)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE,false, false, $platform),
            self::CHARTS_HAXIS_TICKET,
            [
                'title' => 'Nombre de tickets ' . $platform,
                'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                'gridlines' => 10,
                'legend' => 'none',
                'colors' => ['#65b2e8']
            ]
        );
    }

    /**
     * @param array $issues
     * @param string $platform
     * @return ColumnChart|void
     */
    private function chartsEachPlatformPrioHour($issues, $platform)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE,true, true, $platform),
            self::CHARTS_HAXIS_PRIO,
            [
                'title' => 'Temps passés sur ' . $platform,
                'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                'isStacked' => true,
                'gridlines' => 10,
                'colors' => self::CHARTS_COLORS_PRIO
            ]
        );
    }

    /**
     * @param array $issues
     * @param string $platform
     * @return ColumnChart|void
     */
    private function chartsEachPlatformPrioTicket($issues, $platform)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE,true, false, $platform),
            self::CHARTS_HAXIS_PRIO,
            [
                'title' => 'Nombre de tickets sur ' . $platform,
                'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                'isStacked' => true,
                'gridlines' => 10,
                'colors' => self::CHARTS_COLORS_PRIO
            ]
        );
    }

    /**
     * @param array $issues
     * @return array
     */
    private function allChartsPlatform($issues)
    {
        $charts = [];
        $fieldsForCharts = self::ARRAY_PLATFORMS;

        foreach ($fieldsForCharts as $platform) {
            $nameOfChart = $platform !== 'Media Buying' ? $platform : 'MediaBuying';
            $charts += ['tickets' . $nameOfChart . 'Prio' => $this->chartsEachPlatformPrioTicket($issues, $platform)];
            $charts += ['hours' . $nameOfChart . 'Prio' => $this->chartsEachPlatformPrioHour($issues, $platform)];
            $charts += ['tickets' . $nameOfChart => $this->chartsEachPlatformTicket($issues, $platform)];
            $charts += ['hours' . $nameOfChart => $this->chartsEachPlatformHour($issues, $platform)];
        }
        return $charts;
    }

    /**
     * @param array $dataForChart
     * @param array $hAxisArray
     * @param array $options
     * @return ColumnChart|void
     */
    private function buildGoogleChart($dataForChart, $hAxisArray, $options = [])
    {
        if (empty($dataForChart) || empty($hAxisArray) || (count($dataForChart[0]) !== count($hAxisArray))) {
            return;
        }

        array_unshift($dataForChart, $hAxisArray);

        $chart = new ColumnChart();
        $chart->getData()->setArrayToDataTable($dataForChart);
        $chart->getOptions()->getTitleTextStyle()->setFontSize(13);
        $chart->getOptions()->getBar()->setGroupWidth(15);
        $chart->getOptions()->setWidth(460);
        $chart->getOptions()->setHeight(300);
        $chart->getOptions()->getHAxis()->setSlantedText(true);
        $chart->getOptions()->getHAxis()->setSlantedTextAngle(45);
        if (!empty($options)) {
            $chart->getOptions()->setTitle(isset($options['title']) ? $options['title'] : '');
            $chart->getOptions()->getVAxis()->setTitle(isset($options['vAxisTitle']) ? $options['vAxisTitle'] : '');
            $chart->getOptions()->getVAxis()->setLogScale(isset($options['logScale']) ? $options['logScale'] : false);
            $chart->getOptions()->getVAxis()->getGridlines()->setCount(isset($options['gridlines']) ? $options['gridlines'] : -1);
            $chart->getOptions()->getVAxis()->setScaleType(isset($options['scaleType']) ? $options['scaleType'] : null);
            $chart->getOptions()->getLegend()->setPosition(isset($options['legend']) ? $options['legend'] : 'right');
            $chart->getOptions()->setIsStacked(isset($options['isStacked']) ? $options['isStacked'] : false);
            $chart->getOptions()->setColors(isset($options['colors']) ? $options['colors'] : []);
        }

        return $chart;

    }

    /**
     * @param array $issues
     * @param string $typeOfChart
     * @param bool $priority
     * @param bool $temps
     * @param null|string $platform
     * @return array
     */
    private function buildArrayForCharts($issues, $typeOfChart = self::TYPE, $priority = false, $temps = false, $platform = null)
    {
        $result = [];

        if ($typeOfChart === self::TYPE) {
            $fieldsForCharts = self::ARRAY_TYPE_TRACKER;
            $nameOfRow = 'type';
        } elseif ($typeOfChart === self::PLATFORM) {
            $fieldsForCharts = self::ARRAY_PLATFORMS;
            $nameOfRow = 'platform';
        } else {
            return $result;
        }

        $priorityArray = ['Bas', 'Normal', 'Haut', 'Urgent', 'Immédiat'];
        $nbPriorities = count($priorityArray);

        foreach ($fieldsForCharts as $field) {
            $result[] = $priority ? [$field, 0, 0, 0, 0, 0] : [$field, 0];
        }

        foreach ($issues as $issue) {
            $condition = isset($platform) ? $issue['platform'] === $platform : true;
            foreach ($result as $key => $value) {
                if ($condition) {
                    if ($issue[$nameOfRow] === $value[0]) {
                        if ($priority) {
                            for ($i = 0; $i < $nbPriorities; $i++) {
                                if ($priorityArray[$i] === $issue['priority']) {
                                    $temps ? $result[$key][$i + 1] += $issue['timeSpent'] : $result[$key][$i + 1] += 1;
                                }
                            }
                        } else {
                            $temps ? $result[$key][1] += $issue['timeSpent'] : $result[$key][1] += 1;
                        }
                    }
                }
            }
        }

        return $result;
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
     * @param $data
     * @return array
     */
    public function getIssues($data)
    {
        $issueRepository = $this->entityManager->getRepository(Issue::class);
        $date = $this->getDates($data);

        $issues = [];

        if (!empty($date)) {
            if (is_array($date)) {
                $issues = $data['choice']
                    ? $issueRepository->findIssuesByCreatedOnBetween($date['begin'], $date['end'])
                    : $issueRepository->findIssuesByClosedOnBetween($date['begin'], $date['end']);
            } else {
                $issues = $data['choice']
                    ? $issueRepository->findIssuesByCreatedOn($date)
                    : $issueRepository->findIssuesByClosedOn($date);
            }
        }

        foreach ($issues as $key => $issue){
            $issues[$key]['createdOn'] = $issue['createdOn']->format('d/m/Y H:i:s');
            $issues[$key]['closedOn'] = $issue['closedOn']->format('d/m/Y H:i:s');
            unset($issues[$key]['id']);
        }

        return $issues;
    }

    /**
     * @param array $data
     * @return array|string
     */
    private function getDates($data)
    {
        if ($data['begin'] !== null && $data['end'] === null) {
            return $data['begin'];
        } elseif ($data['end'] !== null && $data['begin'] === null) {
            return $data['end'];
        } elseif ($data['begin'] !== null && $data['end'] !== null) {
            return [
                'begin' => $data['begin'],
                'end' => $data['end']
            ];
        }
    }

}
