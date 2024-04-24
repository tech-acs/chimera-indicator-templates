<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Uneca\Chimera\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;

class PopulationPyramid extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;

    public function getData(array $filter): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object)[
              'age_range' => '0-4',
              'range_start' => 0,
              'males' => '55',
              'females' => '56',
            ],
            (object) [
              'age_range' => '5-9',
              'range_start' => 5,
              'males' => '51',
              'females' => '53',
            ],
            (object) [
              'age_range' => '10-14',
              'range_start' => 10,
              'males' => '52',
              'females' => '53',
            ],
            (object) [
              'age_range' => '15-19',
              'range_start' => 15,
              'males' => '42',
              'females' => '45',
            ],
            (object) [
              'age_range' => '20-24',
              'range_start' => 20,
              'males' => '33',
              'females' => '39',
            ],
            (object) [
              'age_range' => '25-29',
              'range_start' => 25,
              'males' => '24',
              'females' => '25',
            ],
            (object) [
              'age_range' => '30-34',
              'range_start' => 30,
              'males' => '32',
              'females' => '32',
            ],
            (object) [
              'age_range' => '35-39',
              'range_start' => 35,
              'males' => '28',
              'females' => '28',
            ],
            (object) [
              'age_range' => '40-44',
              'range_start' => 40,
              'males' => '27',
              'females' => '27',
            ],
            (object) [
              'age_range' => '45-49',
              'range_start' => 45,
              'males' => '21',
              'females' => '19',
            ],
            (object) [
              'age_range' => '50-54',
              'range_start' => 50,
              'males' => '14',
              'females' => '12',
            ],
            (object) [
              'age_range' => '55-59',
              'range_start' => 55,
              'males' => '8',
              'females' => '13',
            ],
            (object) [
              'age_range' => '60-64',
              'range_start' => 60,
              'males' => '8',
              'females' => '11',
            ],
            (object) [
              'age_range' => '65-69',
              'range_start' => 65,
              'males' => '3',
              'females' => '12',
            ],
            (object) [
              'age_range' => '70-74',
              'range_start' => 70,
              'males' => '6',
              'females' => '6',
            ],
            (object) [
              'age_range' => '75-79',
              'range_start' => 75,
              'males' => '1',
              'females' => '3',
            ],
            (object) [
              'age_range' => '80-84',
              'range_start' => 80,
              'males' => '4',
              'females' => '3',
            ],
            (object)[
              'age_range' => '85-89',
              'range_start' => 85,
              'males' => '2',
              'females' => '0',
            ],
            (object)[
              'age_range' => '90-94',
              'range_start' => 90,
              'males' => '0',
              'females' => '1',
            ],
        ]);
    }

    protected function getTraces(Collection $inputData, string $filter): array
    {
        $result = $inputData;
        $total = 0;
        foreach ($result as $row) {
            $total += $row->males + $row->females;
            $row->males_negated = -1 * $row->males;
        }

        $result = $result->map(function ($row) use ($total){
            $row->males = $total != 0 ? round(($row->males /$total ) * 100 , 1) : 0;
            $row->females = $total != 0 ? round(($row->females /$total ) * 100 , 1) : 0;
            $row->males_negated = $total != 0 ? round(($row->males_negated /$total ) * 100 , 1) : 0;
            return $row;
        });

        $traceMales = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $result->pluck('males_negated')->all(),
                'y' => $result->pluck('age_range')->all(),
                'text' => $result->pluck('males')->all(),
                'texttemplate' => "%{text} %",
                'hovertemplate' => "%{text} %",
                'orientation' => 'h',
                'name' => __('Males'),
            ]
        );
        $traceFemales = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $result->pluck('females')->all(),
                'y' => $result->pluck('age_range')->all(),
                'text' => $result->pluck('females')->all(),
                'hoverinfo' => 'text+y',
                'orientation' => 'h',
                'name' => __('Females'),
            ]
        );
        return [$traceMales, $traceFemales];
    }

    protected function getLayout(string $filter): array
    {
        $layout = parent::getLayout($filter);
        $layout['xaxis']['type'] = '';
        $layout['xaxis']['tickmode'] = '';
        $layout['xaxis']['title']['text'] = __('Percentage of population ') ;
        $layout['xaxis']['showticklabels'] = false;
        $layout['barmode'] = 'relative';
        if ($this->isSampleData) {
            $layout['annotations'] = [[
                'text' => __('SAMPLE'),
                'textangle' => -30,
                'opacity' => 0.12,
                'xref' => 'paper',
                'yref' => 'paper',
                'font' => ['color' => 'black', 'size' => 120]
            ]];
        }
        return $layout;
    }

}
