<?php

namespace App\Service;

use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart;

/**
 * Class ReportingDataBuilder
 * @package App\Service
 */
class ReportingDataBuilder
{
    private const TYPE_TRACQUEUR = 'Type traqueur';
    private const PLATEFORME = 'Plateforme';
    private const CHARTS_COLORS_PRIO = ['#7effdd', '#307771', '#ffcb3b', '#ef723d', '#c72539'];
    private const CHARTS_HAXIS_PRIO = ['Nombre', 'Bas', 'Normal', 'Haut', 'Urgent', 'Immédiat'];
    private const CHARTS_HAXIS_HOUR = ['Nombre', 'heures'];
    private const CHARTS_HAXIS_TICKET = ['Nombre', 'tickets'];
    private const CHARTS_VAXIS_HOUR = 'Nombre d\'heures';
    private const CHARTS_VAXIS_TICKET = 'Nombre de tickets';
    private const ARRAY_PLATFORMS = [
        'Console', 'Infra', 'LMT', 'Media Buying', 'Nativious', 'Performance', 'Publishing', 'RGPD', 'Wavecy', 'Wordpress'
    ];
    private const ARRAY_TYPE_TRACKER = [
        'Autre', 'Bug', 'Evolution', 'Erreur utilisateur', 'Export', 'Formation',
        'Mise en place opérationnelle / Configuration', 'Plateforme Externe', 'RGPD', 'Traffic'
    ];

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
            if (isset($issue['Temps passé'])) {
                if ($issue['Temps passé'] > 0) {
                    $totalTimeSpend += $issue['Temps passé'];
                } elseif ($issue['Temps passé'] == 0) {
                    $issuesTimeSpendUndefined[] = $issue['Lien'];
                }
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
        $titleHeuresTraqueur = 'Temps passés par type de tracker';
        $titleHeuresPlateforme = 'Temps passés par plateforme';
        $titleTicketsTraqueur = 'Nombre de tickets par type de tracker';
        $titleTicketsPlateforme = 'Nombre de tickets par plateforme';

        $charts = [
            'ticketsTraqueurPrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleTicketsTraqueur,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            ),
            'ticketsPlateformePrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATEFORME, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleTicketsPlateforme,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            ),
            'ticketsTraqueur' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR),
                self::CHARTS_HAXIS_TICKET,
                [
                    'title' => $titleTicketsTraqueur,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'gridlines' => 10,
                    'legend' => 'none',
                    'colors' => ['#ffc844']
                ]
            ),
            'ticketsPlateforme' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATEFORME),
                self::CHARTS_HAXIS_TICKET,
                [
                    'title' => $titleTicketsPlateforme,
                    'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                    'gridlines' => 10,
                    'legend' => 'none',
                    'colors' => ['#65b2e8']
                ]
            ),
            'heuresTraqueur' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, false, true),
                self::CHARTS_HAXIS_HOUR,
                [
                    'title' => $titleHeuresTraqueur,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'logScale' => true,
                    'scaleType' => 'mirrorLog',
                    'legend' => 'none',
                    'colors' => ['#ffc844']
                ]
            ),
            'heuresPlateforme' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATEFORME, false, true),
                self::CHARTS_HAXIS_HOUR,
                [
                    'title' => $titleHeuresPlateforme,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'logScale' => true,
                    'scaleType' => 'mirrorLog',
                    'legend' => 'none',
                    'colors' => ['#65b2e8']
                ]
            ),
            'heuresTraqueurPrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, true, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleHeuresTraqueur,
                    'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                    'isStacked' => true,
                    'gridlines' => 10,
                    'colors' => self::CHARTS_COLORS_PRIO
                ]
            ),
            'heuresPlateformePrio' => $this->buildGoogleChart(
                $this->buildArrayForCharts($issues, self::PLATEFORME, true, true),
                self::CHARTS_HAXIS_PRIO,
                [
                    'title' => $titleHeuresPlateforme,
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
     * @param $plateforme
     * @return ColumnChart|void
     */
    private function chartsEachPlatformHour($issues, $plateforme)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, false, true, $plateforme),
            self::CHARTS_HAXIS_HOUR,
            [
                'title' => 'Temps passés sur ' . $plateforme,
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
     * @param $plateforme
     * @return ColumnChart|void
     */
    private function chartsEachPlatformTicket($issues, $plateforme)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, false, false, $plateforme),
            self::CHARTS_HAXIS_TICKET,
            [
                'title' => 'Nombre de tickets ' . $plateforme,
                'vAxisTitle' => self::CHARTS_VAXIS_TICKET,
                'gridlines' => 10,
                'legend' => 'none',
                'colors' => ['#65b2e8']
            ]
        );
    }

    /**
     * @param array $issues
     * @param string $plateforme
     * @return ColumnChart|void
     */
    private function chartsEachPlatformPrioHour($issues, $plateforme)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, true, true, $plateforme),
            self::CHARTS_HAXIS_PRIO,
            [
                'title' => 'Temps passés sur ' . $plateforme,
                'vAxisTitle' => self::CHARTS_VAXIS_HOUR,
                'isStacked' => true,
                'gridlines' => 10,
                'colors' => self::CHARTS_COLORS_PRIO
            ]
        );
    }

    /**
     * @param array $issues
     * @param string $plateforme
     * @return ColumnChart|void
     */
    private function chartsEachPlatformPrioTicket($issues, $plateforme)
    {
        return $this->buildGoogleChart(
            $this->buildArrayForCharts($issues, self::TYPE_TRACQUEUR, true, false, $plateforme),
            self::CHARTS_HAXIS_PRIO,
            [
                'title' => 'Nombre de tickets sur ' . $plateforme,
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

        foreach ($fieldsForCharts as $plateforme) {
            $nameOfChart = $plateforme !== 'Media Buying' ? $plateforme : 'MediaBuying';
            $charts += ['tickets' . $nameOfChart . 'Prio' => $this->chartsEachPlatformPrioTicket($issues, $plateforme)];
            $charts += ['heures' . $nameOfChart . 'Prio' => $this->chartsEachPlatformPrioHour($issues, $plateforme)];
            $charts += ['tickets' . $nameOfChart => $this->chartsEachPlatformTicket($issues, $plateforme)];
            $charts += ['heures' . $nameOfChart => $this->chartsEachPlatformHour($issues, $plateforme)];
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
        $chart->getOptions()->getBar()->setGroupWidth('50%');
        $chart->getOptions()->setWidth(440);
        $chart->getOptions()->setHeight(300);
        $chart->getOptions()->getHAxis()->setSlantedText(true);
        $chart->getOptions()->getHAxis()->setSlantedTextAngle(45);
        if (!empty($options)) {
            $chart->getOptions()->setTitle(isset($options['title']) ? $options['title'] : '');
            $chart->getOptions()->getVAxis()->setTitle(isset($options['vAxisTitle']) ? $options['vAxisTitle'] : '');
            $chart->getOptions()->getVAxis()->setLogScale(isset($options['logScale']) ? $options['logScale'] : false);
            $chart->getOptions()->getVAxis()->getGridlines()->setCount(isset($options['gridlines']) ? $options['gridlines'] : -1);
            $chart->getOptions()->getVAxis()->setScaleType(isset($options['scaleType']) ? $options['scaleType'] : null);
            $chart->getOptions()->getLegend()->setPosition(isset($options['legend']) ? $options['legend'] : 'top');
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
     * @param null|string $plateforme
     * @return array
     */
    private function buildArrayForCharts($issues, $typeOfChart, $priority = false, $temps = false, $plateforme = null)
    {
        $result = [];

        if ($typeOfChart === self::TYPE_TRACQUEUR || $typeOfChart === self::PLATEFORME) {

            $priorityArray = ['Bas', 'Normal', 'Haut', 'Urgent', 'Immédiat'];
            $nbPriorities = count($priorityArray);

            if ($typeOfChart === self::TYPE_TRACQUEUR) {
                $fieldsForCharts = self::ARRAY_TYPE_TRACKER;
                $nameOfRow = 'Type de traqueur';
            } else {
                $fieldsForCharts = self::ARRAY_PLATFORMS;
                $nameOfRow = 'Plateforme';
            }

            foreach ($fieldsForCharts as $field) {
                $result[] = $priority ? [$field, 0, 0, 0, 0, 0] : [$field, 0];
            }

            foreach ($issues as $issue) {
                $condition = isset($plateforme) ? $issue['Plateforme'] === $plateforme : true;

                foreach ($result as $key => $value) {
                    if ($condition) {
                        if ($issue[$nameOfRow] === $value[0]) {
                            if ($priority) {
                                for ($i = 0; $i < $nbPriorities; $i++) {
                                    if ($priorityArray[$i] === $issue['Priorité']) {
                                        $temps ? $result[$key][$i + 1] += $issue['Temps passé'] : $result[$key][$i + 1] += 1;
                                    }
                                }
                            } else {
                                $temps ? $result[$key][1] += $issue['Temps passé'] : $result[$key][1] += 1;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

}
