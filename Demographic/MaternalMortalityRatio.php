<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Ramsey\Uuid\Type\Integer;
use Uneca\Chimera\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Interfaces\LineChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class MaternalMortalityRatio extends Chart implements BarChart, LineChart
{
    use FilterBasedAxisTitle;
    private bool $isSampleData = false;
    protected int $EXPECTEDVALUE = 342;
    public function getData(array $filter = []): Collection
    {
        $this->isSampleData = true;
        return collect([
            (object)[
                'area_name' => 'Area 1',
                'area_code' => '1',
                'birth' => '100000',
                'death' => '100'
            ],
            (object)[
                'area_name' => 'Area 2',
                'area_code' =>'2',
                'birth' => '100000',
                'death' => '100'
            ],
            (object)[
                'area_name' => 'Area 3',
                'area_code' =>'3',
                'birth' => '100000',
                'death' => '410'
            ],
            (object)[
                'area_name' => 'Area 4',
                'area_code' =>'4',
                'birth' => '100000',
                'death' => '100'
            ],
            (object)[
                'area_name' => 'Area 5',
                'area_code' =>'5',
                'birth' => '100000',
                'death' => '200'
            ],
            ]
        );
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
            $area->birth = $dataKeyByAreaCode[$area->code]->birth ?? 0;
            $area->death = $dataKeyByAreaCode[$area->code]->death ?? 0;
            $area->expected = $this->EXPECTEDVALUE; // world bank 2017
            $area->rate = Helpers::safeDivide($area->death, $area->birth) * 100000 ;
            return $area;
        });

        $totalBirth = $data->sum('birth');
        $totalDeath = $data->sum('death');
        $totalRate = Helpers::safeDivide($totalDeath, $totalBirth) * 100000 ;
        $data[]= (object) ['death' => $totalDeath,'birth' => $totalBirth, 'rate' => $totalRate, 'expected'=> $this->EXPECTEDVALUE , 'code' => '','name' => __('All ').$this->getAreaBasedAxisTitle($filterPath)];

        $traceBirthRate = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('rate')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'marker' => ['color' => '#1e3b87'],
                'name' => 'Maternal deaths (per 100000 live births)',
            ]
        );

        $traceExpected = array_merge(
            $this::LineTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('expected')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'name' => 'Expected value',
            ]
        );

        return [$traceBirthRate, $traceExpected];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout=parent::getLayout($filterPath);

        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = "# of maternal deaths";
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
