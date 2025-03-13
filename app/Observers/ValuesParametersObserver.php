<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ValuesParametersObserver
{
    public function saved(ValuesParameters $valuesParameters)
    {
        DB::transaction(function () use ($valuesParameters) {
            // 1️⃣ TimeID bo‘yicha barcha mos Calculator yozuvlarini olish
            $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
            foreach ($calculators as $calculator) {
                $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;
                if (!$calculateArray) continue;
    
                $parameterIdsInCalculate = [];
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterIdsInCalculate[] = substr($item, 4);
                    }
                }
                if (empty($parameterIdsInCalculate)) continue;
                if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) continue;
    
                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) continue;
    
                $result = null;
                $numberBuffer = "";
                $values = [];
                $operatorStack = [];
                $parameters = [];
    
                // ✅ **TID larni `graphic_times` bilan solishtirib, `values_parameters` dan barcha mos keluvchi qiymatlarni olish**
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
    
                        // 🔹 `graphic_times` jadvalidan `Name` ni olish
                        $graphicTimeName = DB::table('graphic_times')
                            ->where('id', $timeId)
                            ->value('Name');
    
                        if (!$graphicTimeName) {
                            \Log::error("graphic_times dagi `Name` topilmadi! TimeID: $timeId");
                            continue;
                        }
    
                        // // 🔹 Shu `Name` ga mos keladigan barcha `TimeID` larni olish
                        // $relatedTimeIds = DB::table('graphic_times')
                        //     ->where('Name', $graphicTimeName)
                        //     ->pluck('id');
    
                        // 🔹 `values_parameters` dan shu `ParametersID` bo‘yicha **barcha `Value` larni olish**
                        $paramValues = ValuesParameters::where('ParametersID', $parameterId)
                            // ->whereIn('TimeID', $relatedTimeIds)
                            ->where('TimeStr', $graphicTimeName)
                            ->where('Created', $valuesParameters->Created)
                            ->pluck('Value')
                            ->toArray();
    
                        if (empty($paramValues)) {
                            $missingParameters = true;
                        }
    
                        // ✅ **Agar bir nechta qiymat bo‘lsa, ularni yig‘indisini olish (yoki boshqa operator qo‘llash mumkin)**
                        $parameters[$parameterId][$timeId] = array_sum($paramValues) ?? 0;
                    }
                }
    
                // ✅ **Hisoblash**
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
                        $value = $parameters[$parameterId][$timeId] ?? 0;
                        $numberBuffer .= (string) $value;
                    } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                        if ($numberBuffer !== "") {
                            $values[] = $numberBuffer;
                            $numberBuffer = "";
                        }
                        if ($item === '÷') {
                            $item = '/';
                        }
                        if ($item === '=') {
                            break;
                        } elseif ($item === '(') {
                            $values[] = $item;
                        } elseif ($item === ')') {
                            while (!empty($operatorStack) && end($operatorStack) !== '(') {
                                $values[] = array_pop($operatorStack);
                            }
                            if (!empty($operatorStack) && end($operatorStack) === '(') {
                                array_pop($operatorStack);
                            }
                            $values[] = $item;
                        } else {
                            $values[] = $item;
                        }
                    } else {
                        $numberBuffer .= $item;
                    }
                }
    
                if ($numberBuffer !== "") {
                    $values[] = $numberBuffer;
                }
    
                $calculateString = implode(' ', $values);
    
                try {
                    if (empty($calculateString)) {
                        throw new \Exception("Bo‘sh matematik ifoda!");
                    }
    
                    \Log::info("Hisoblash ifodasi: $calculateString");
                    $result = eval("return ($calculateString);");
    
                    if ($result === false) {
                        throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                    }
                } catch (\Throwable $e) {
                    \Log::error("Hisoblashda xato: " . $e->getMessage());
                    continue;
                }
    
                // ✅ **Bazaga yozish**
                ValuesParameters::withoutEvents(function () use ($valuesParameters, $param, $result) {
                    $data = [
                        'ParametersID' => (string) $param->ParametersID,
                        'SourceID' => (string) $param->SourceID,
                        'GTid' => (string) $valuesParameters->TimeID,
                        'TimeStr' => $valuesParameters->TimeStr, 
                        'Value' => round($result, 2),
                        'GraphicsTimesID' => (string) $param->GrapicsID,
                        'BlogID' => (string) $param->BlogsID,
                        'FactoryStructureID' => (string) $param->FactoryStructureID,
                        'ChangeID' => $valuesParameters->ChangeID,
                        'created_at' => now(),
                        'Created' => $valuesParameters->Created,
                    ];
                    $newOrUpdateRecord = ValuesParameters::updateOrCreate(
                        [
                            'TimeID' => $data['GTid'],
                            'ParametersID' => $data['ParametersID'],
                            'SourcesID' => $data['SourceID'],
                            'Created' => $valuesParameters->Created,
                            'TimeStr' => $data['TimeStr'],
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'Value' => $data['Value'],
                            'GraphicsTimesID' => $data['GraphicsTimesID'],
                            'BlogID' => $data['BlogID'],
                            'FactoryStructureID' => $data['FactoryStructureID'],
                            'ChangeID' => $valuesParameters->ChangeID,
                            'Created' => $valuesParameters->Created,
                            'updated_at' => now(),
                        ]
                    );
    
                    \Log::info("✅ Bazaga yozildi: " . json_encode($newOrUpdateRecord));
                });
    
                // ✅ **Rekursiya: Bog‘liq formulalarni qayta hisoblash**
                $dependentCalculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
                foreach ($dependentCalculators as $depCalculator) {
                    $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
                    if (!$depCalculateArray) continue;
    
                    foreach ($depCalculateArray as $item) {
                        if ($item === "Pid={$param->ParametersID}") {
                            $dependentValuesParameters = ValuesParameters::where('ParametersID', $param->ParametersID)
                                ->whereIn('TimeID', $relatedTimeIds)
                                ->first();
    
                            if ($dependentValuesParameters) {
                                $this->saved($dependentValuesParameters);
                            }
                            break;
                        }
                    }
                }
            }
        });
    }
    
    
    
}
