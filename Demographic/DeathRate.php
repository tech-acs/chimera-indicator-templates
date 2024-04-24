<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Uneca\Chimera\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class DeathRate extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    public function getData(array $filter = []): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object)[
                'area_name' => 'Area 1',
                'area_code' => '1',
                'total' => '1000',
                'death' => '1',
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'total' => '2000',
                'death' => '1',
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'total' => '3000',
                'death' => '3',
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'total' => '4000',
                'death' => '4',
            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'total' => '5000',
                'death' => '5',
            ],
        ]);
    }

    protected function getTraces(Collection $data, string $filterPath): array
    {
        $areas = (new AreaTree())->areas($filterPath);
        $dataKeyByAreaCode = $data->keyBy('area_code');
        if($this->isSampleData){
            $areas = $data->map(function ($item) {
                return (object) [
                    'code' => $item->area_code,
                    'name' => $item->area_name,
                ];
            });
        }
        $data = $areas->map(function ($area) use ($dataKeyByAreaCode) {
            $area->total = $dataKeyByAreaCode[$area->code]->total ?? 0;
            $area->death = $dataKeyByAreaCode[$area->code]->death ?? 0;
            $area->rate = Helpers::safeDivide($area->death, $area->total) * 1000;

            return $area;
        });

        $totaldeath = $data->sum('death');
        $totalPopulation = $data->sum('total');
        $totalRate = Helpers::safeDivide($totaldeath, $totalPopulation) * 1000;
        $data[]= (object) ['total' => $totalPopulation,'death' => $totaldeath, 'rate' => $totalRate, 'code' => '','name' => __('All ').$this->getAreaBasedAxisTitle($filterPath)];

        $traceDeath = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('rate')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'marker' => ['color' => '#1e3b87'],
                'name' => 'Crude death rate per 1000 population',
            ]
        );
        return [ $traceDeath];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout=parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = "Rate";
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
