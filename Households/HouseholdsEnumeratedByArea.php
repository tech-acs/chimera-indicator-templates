<?php

namespace App\IndicatorTemplates\Households;

use App\Http\Livewire\Chart;
use App\Services\Interfaces\BarChart;
use App\Services\Interfaces\LineChart;
use App\Services\AreaTree;
use App\Services\Traits\FilterBasedAxisTitle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HouseholdsEnumeratedByArea extends Chart implements BarChart, LineChart
{
    use FilterBasedAxisTitle;
    public bool $IsSample = false;
    public string $help = "
        This graph displays the total number of households aggregated by area. By default, the aggregation is done by regions, subsequently users 
        can select areas to drill down specific perfectures, commune,canton or EAs.<br><br>
        The values are calculated by counting the total number of households after grouping them into their respective areas.<br><br>
        <i>Note: Households are counted from field information_generale.</i>
    ";
    protected function loadInputData(array $filter): Collection
    {
        $this->IsSample =true;
        return \collect([
            (object) [
                'area_name' => 'Area 1',
                'area_code' => 'area1',
                'total' => 100,
            ],
            (object) [
                'area_name' => 'Area 2',
                'area_code' => 'area2',
                'total' => 200,
            ],
            (object) [
                'area_name' => 'Area 3',
                'area_code' => 'area3',
                'total' => 300,
            ],
            (object) [
                'area_name' => 'Area 4',
                'area_code' => 'area4',
                'total' => 400,
            ],
            ]
        );
    }
  
    protected function getTraces(Collection $inputData, array $filter): array
    {
        
        $result = $inputData;
      //  $this->setNoData($result);
        $finestResolutionFilterPath = $this->getFinestResolutionFilterPath($filter);
        $areas = (new AreaTree())->areas($finestResolutionFilterPath, checksumSafe: false)->pluck('name', 'code');
        if(!$this->IsSample){
            $result = $result->map(function($item) use ($areas) {
                $item->area_name = $areas[$item->area_code];
                return $item;
            });
        }      

        $this->threshold=collect([
            ['green' => 100,'amber' => 90]
        ]);
        
        $now = Carbon::now();
        $dates = [
            'start_date' => $this->indicator->getQuestionnaire()->start_date,
            'end_date' => $this->indicator->getQuestionnaire()->end_date
        ];
        $start_date= Carbon::parse($dates['start_date'])->subDays(1);
        $end_date= Carbon::parse($dates['end_date']);
      
        $total_Enum_days=$start_date->diffInDays($end_date,false); //4 days        
        $days_Since_enum_start=$start_date->diffInDays($now,false);
        
        if($days_Since_enum_start > $total_Enum_days) {
            $days_Since_enum_start=$total_Enum_days;            
        }
        
        $result = $result->map(function ($row) use ($days_Since_enum_start,$total_Enum_days) {
            $row->bar_width=0.7;
            if(!isset($row->total)){
                $row->total = 0;
            } 
            if(!isset($row->target)){
                $row->target = 0;
            }

           if(!$row->target == null && $days_Since_enum_start > 0) {
                $row->expected = round($row->target *$days_Since_enum_start/$total_Enum_days,0);
           }
           else{
                $row->expected = 0;
           }

            return $row;
        });
  
        $grandTotal_target  =0;
        $grandTotal_hh=0;

        $aggregate_label = $this->getAreaBasedAxisTitle($filter);
        foreach($result as $row)
         {
            $grandTotal_hh += $row->total;             
            $grandTotal_target += $row->target;

         }

        $aggregated=collect([
            [   'total' => $grandTotal_hh , 
                'expected' => ($grandTotal_target *$days_Since_enum_start/$total_Enum_days),
                'bar_width' => 0.7,
                'area_code' => '',
                'area_name' =>  $aggregate_label]
        ]);
        $traceActualAgrg = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $aggregated->pluck('area_name')->all(),
                'y' => $aggregated->pluck('total')->all(),
                'width' => $aggregated->pluck('bar_width')->all(),
                'name' => __(":area's actual", ['area' => $aggregate_label]),
            ]
        );
        $traceActual = array_merge(
            $this::BarTraceTemplate,
            [
                'x' => $result->pluck('area_name')->all(),
                'y' => $result->pluck('total')->all(),
                'width' => $result->pluck('bar_width')->all(),
                'name' => __('Actual'),
            ]
        );

        return [$traceActual, $traceActualAgrg,];

    }

 

    protected function getLayout(array $filter): array
    {
            $layout = parent::getLayout($filter);
            $layout['xaxis']['title']['text'] = $this->getAreaBasedAxisTitle($filter);;
            $layout['yaxis']['title']['text'] = __("# of households");
            $layout['barmode'] = 'overlay';
            if ($this->IsSample) {
                $layout['colorway'] = [ '#dcdcdc','#808080'];
            }
            return $layout;

    }

}