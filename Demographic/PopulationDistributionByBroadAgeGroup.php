<?php

namespace App\IndicatorTemplates\Demographic;

use Illuminate\Support\Collection;
use Uneca\Chimera\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;
use Uneca\Chimera\Services\Helpers;

class PopulationDistributionByBroadAgeGroup extends Chart implements BarChart
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
                'group1' => '15',
                'group2' => '25',
                'group3' => '35',
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'group1' => '45',
                'group2' => '55',
                'group3' => '65',
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'group1' => '75',
                'group2' => '85',
                'group3' => '95',
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'group1' => '105',
                'group2' => '115',
                'group3' => '125',
            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'group1' => '135',
                'group2' => '145',
                'group3' => '155',
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
            try {

                    $area->group1_percentp12 = 0;
                    $area->group2_percentp12 = 0;
                    $area->group3_percentp12 = 0;
                    $area->group1 = $dataKeyByAreaCode[$area->code]->group1 ;
                    $area->group2 = $dataKeyByAreaCode[$area->code]->group2 ;
                    $area->group3 = $dataKeyByAreaCode[$area->code]->group3 ;

                if (isset($dataKeyByAreaCode[$area->code])) {
                    $tot = $dataKeyByAreaCode[$area->code]->group1 + $dataKeyByAreaCode[$area->code]->group2 + $dataKeyByAreaCode[$area->code]->group3;
                    $area->group1_percentp12 = (Helpers::safeDivide($dataKeyByAreaCode[$area->code]->group1, $tot) * 100);
                    $area->group2_percentp12 = (Helpers::safeDivide($dataKeyByAreaCode[$area->code]->group2, $tot) * 100);
                    $area->group3_percentp12 = (Helpers::safeDivide($dataKeyByAreaCode[$area->code]->group3, $tot) * 100);
                }
            } catch (\Exception $exception) {
                //
            }
            return $area;
        });

        $group1_total = $data->sum('group1');
        $group2_total = $data->sum('group2');
        $group3_total = $data->sum('group3');
        $grand_total = $group1_total+$group2_total+$group3_total;

        $grand_percentp12_1 = (Helpers::safeDivide($group1_total, $grand_total) * 100);
        $grand_percentp12_2 = (Helpers::safeDivide($group2_total, $grand_total) * 100);
        $grand_percentp12_3 = (Helpers::safeDivide($group3_total, $grand_total) * 100);

        $data[] = (object)['group1' => $group1_total,'group2' => $group2_total,'group3' => $group3_total,
                    'group1_percentp12' => $grand_percentp12_1,'group2_percentp12' => $grand_percentp12_2,'group3_percentp12' => $grand_percentp12_3,
                    'total'=>  $grand_total,'area_code'=> '','name'=>__('All ').$this->getAreaBasedAxisTitle($filterPath)];
        $trace1 = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('group1_percentp12')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'name' => '< 15 years',
            ]
        );
        $trace2 = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('group2_percentp12')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'name' => '15 to 64 years',
            ]
        );
        $trace3 = array_merge(
            $this::PercentageBarTraceTemplate,
            [
                'x' => $data->pluck('name')->all(),
                'y' => $data->pluck('group3_percentp12')->all(),
                'texttemplate' => "%{value:.2f}",
                'hovertemplate' => "%{label}<br> %{value:.2f}",
                'name' => '> 64 years',
            ]
        );

        return [$trace1, $trace2, $trace3];
    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);
        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = "# of households";
        $layout['barmode'] = 'stack';
        $layout['colorway'] = ['#1e3b87', '#c99c25', '#6f066f', '#7a37aa', '#a30538', '#ff0506', '#dba61f', '#ff6f06', '#fea405', '#ffff05', '#a3d804', '#056e05', '#3939d9', '#0579cc'];
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
