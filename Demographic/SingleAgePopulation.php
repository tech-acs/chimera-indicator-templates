<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Uneca\Chimera\Http\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Services\BreakoutQueryBuilder;
use Uneca\Chimera\Services\QueryFragmentFactory;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class SingleAgePopulation extends Chart implements BarChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    public function getData(array $filter = []): Collection
    {
        $this->isSampleData = true;
        return collect(\range(0, 65))->map(function ($item) {
            return (object) [
                'age_range' => $item,
                'frequency' => rand(340, 360),
            ];
        });
    }

    protected function getTraces(Collection $data, string $filterPath): array
    {
        
        $data = $data->sortBy('age_range', SORT_REGULAR, false);
        $data = $data->map(function ($row) {
            if($row->age_range == 65)
                $row->age_range = '65+';
            return $row;
        });

        $traceAge = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $data->pluck('age_range')->all(),
                'y' => $data->pluck('frequency')->all(),
                'texttemplate' => "%{text} %",
                'hovertemplate' => "%{text} %",
                'marker' => ['color' => '#1e3b87'],
                'name' => 'Single age',
            ]
        );
        return [$traceAge];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = "Age"; 
        $layout['yaxis']['title']['text'] = "Frequency";

        $layout['xaxis']['range'] = [-0.5, 25.5];
        $layout['xaxis']['rangeselector']['buttons'] = [['step' => 'all', 'label' => 'Show all']];
        $layout['xaxis']['rangeselector']['x'] = 0.9;
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
