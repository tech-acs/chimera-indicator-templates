<?php

namespace App\IndicatorTemplates\Households;

use App\Http\Livewire\Chart;
use App\Services\AreaTree;
use App\Services\Interfaces\BarChart;
use App\Services\Interfaces\LineChart;
use App\Services\Traits\FilterBasedAxisTitle;
use Illuminate\Support\Collection;

class AverageHouseholdSizeByArea extends Chart implements BarChart, LineChart
{
    use FilterBasedAxisTitle;
    public bool $IsSample = false;

    protected function loadInputData(array $filter): Collection
    {
        $this->IsSample = true;
        return \collect([
           (object) [
                'area_name' => 'Area 1',
                'area_code' => 'area1',
                'target' => 5.6,
                'total_population' => 100,
                'total_hh' => 20,
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => 'area2',
                'target' => 5.6,
                'total_population' => 200,
                'total_hh' => 40,
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => 'area3',
                'target' => 5.6,
                'total_population' => 300,
                'total_hh' => 60,
            ],
            ]
        );  
    }

    protected function getTraces(Collection $inputData, array $filter): array
    {
        $result = $inputData;
        $finestResolutionFilterPath = $this->getFinestResolutionFilterPath($filter);
        $areas = (new AreaTree())->areas($finestResolutionFilterPath, checksumSafe: false)->pluck('name', 'code');
        if(!$this->IsSample) {
            $result = $result->map(function($item) use ($areas) {
                $item->area_name = $areas[$item->area_code];
                return $item;
            });
        }
        $totalTotal_population = 0;
        $totalTotal_hh = 0;

        foreach ($result as $row) {
            $totalTotal_hh += $row->total_hh;
            $totalTotal_population += $row->total_population;
        }

        $areas = collect($areas);

        $result = $result->map(function ($row) {
            if (!isset($row->total_population)) {
                $row->average_HH_size = 0;
            } else
                $row->average_HH_size =$row->total_population / $row->total_hh;
                $row->s_average_HH_size= $row->average_HH_size;
                $row->target = 5.6;
            return $row;
        });
        $aggregate_label = $this->getAreaBasedAxisTitle($filter);


        $aggregated = collect([
            ['average_HH_size' => ($totalTotal_hh != 0 ? $totalTotal_population / $totalTotal_hh : 0),
             's_average_HH_size' => ($totalTotal_hh != 0 ? $totalTotal_population / $totalTotal_hh : 0),
             'area_name' =>         $aggregate_label 
            ]
        ]);

        $traceAggregated = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $aggregated->pluck('area_name')->all(),
                'y' => $aggregated->pluck('average_HH_size')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'name' => __(":area's average household size", ['area' => $aggregate_label]),
            ]
        );

        $traceDaily = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $result->pluck('area_name')->all(),
                'y' => $result->pluck('average_HH_size')->all(),        
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'name' => __('Average household size'),
            ]
        );

        return [ $traceDaily,$traceAggregated,];
    }

    protected function getLayout(array $filter): array
    {
            $layout = parent::getLayout($filter);
            $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filter);
            $layout['yaxis']['title']['text'] = __("# of persons");
            if($this->IsSample){
                $layout['colorway'] = [ '#dcdcdc','#808080'];
            }
            return $layout;
    }
}
