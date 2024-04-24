<?php

namespace App\IndicatorTemplates\Performance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Uneca\Chimera\Livewire\Chart;
use Uneca\Chimera\Interfaces\BarChart;
use Uneca\Chimera\Services\AreaTree;
use Uneca\Chimera\Services\Helpers;
use Uneca\Chimera\Traits\FilterBasedAxisTitle;

class HouseholdsEnumeratedAgainstTarget  extends Chart implements BarChart
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

            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => '2',
                'total' => '1500',

            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => '3',
                'total' => '1100',

            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => '4',
                'total' => '900',

            ],
            (object) [
                'area_name' => 'Area 5',
                'area_code' => '5',
                'total' => '800',

            ],
        ]);
    }

    protected function getTraces(Collection $data, string $filterPath): array
    {
        $areas = (new AreaTree())->areas($filterPath, nameOfReferenceValueToInclude: 'number_of_hh');
        if($this->isSampleData){
            $areas = $data->map(function ($item) {
                return (object) [
                    'code' => $item->area_code,
                    'name' => $item->area_name,
                    'value' => \round($item->total * (1 +rand(1, 100) / 100)),
                ];
            });
        }

        $questionnaire = $this->indicator->getQuestionnaire();

        $endDate = $questionnaire->end_date;

        $todayDate = Carbon::now()->format('Y-m-d');

        if($todayDate > $endDate->format('Y-m-d')){
            $todayDate = $endDate->format('Y-m-d');
        }

        $now = Carbon::now();
        $startDate = $questionnaire->start_date->subDays(1);

        $total_Enum_days=$startDate->diffInDays($endDate,false);
        $days_Since_enum_start=$startDate->diffInDays($now,false);
        $day_count_yesterday=$startDate->diffInDays($now,false) - 1;

        if($days_Since_enum_start > $total_Enum_days) {
            $days_Since_enum_start = $total_Enum_days;
        }
        if($day_count_yesterday > $total_Enum_days) {
            $day_count_yesterday = $total_Enum_days;
        }


        $dataKeyByAreaCode = $data->keyBy('area_code');
        $result = $areas->map(function ($area) use ($dataKeyByAreaCode,$days_Since_enum_start, $total_Enum_days) {
            $area->expected_value = Helpers::safeDivide($days_Since_enum_start, $total_Enum_days) * $area->value??0;
            $area->bar_width = 0.7;
            $area->total = $dataKeyByAreaCode[$area->code]->total ?? 0;
            return $area;
        });

        $traceTodayTarget = array_merge(
            $this::ValueTraceTemplate,
            [
                'x' => $result->pluck('name')->all(),
                'y' => $result->pluck('expected')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'textposition' => 'outside',
                'name' => __("Today's target"),
                'marker' => ['color' => '#c5c5c5','opacity'=>0.4],
            ]
        );
        $traceActual = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $result->pluck('name')->all(),
                'y' => $result->pluck('total')->all(),
                'texttemplate' => "%{value:.0f}",
                'hovertemplate' => "%{label}<br> %{value:.0f}",
                'width' => $result->pluck('bar_width')->all(),
                'name' => __('Mapped households'),
                'marker' => ['color' => '#1e3b87'],
            ]
        );
        return [$traceTodayTarget, $traceActual];

    }

    protected function getLayout(string $filterPath): array
    {
        $layout = parent::getLayout($filterPath);
        $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filterPath);
        $layout['yaxis']['title']['text'] = __("# of households");
        $layout['barmode'] = 'overlay';
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
