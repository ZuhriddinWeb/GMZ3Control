<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ValuesParametersObserver
{private $recursionDepth = 0;
    private $maxRecursionDepth = 10; // 10 martadan ko‘p qayta ishlamasin
    
    private function calculateFormula(ValuesParameters $valuesParameters)
    {
        // Agar rekursiya limitga yetgan bo‘lsa, davom etmaydi
        if ($this->recursionDepth >= $this->maxRecursionDepth) {
            logger()->warning("⚠ Rekursiya limitga yetdi. Formula ishlashni to‘xtatdi.");
            return;
        }
    
        $this->recursionDepth++; // Rekursiya darajasini oshirish
    
        $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
    
        foreach ($calculators as $calculator) {
            $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;
    
            if (!$calculateArray) {
                continue;
            }
    
            $parameterIdsInCalculate = [];
            foreach ($calculateArray as $item) {
                if (strpos($item, 'Pid=') === 0) {
                    $parameterIdsInCalculate[] = substr($item, 4);
                }
            }
    
            if (!$this->areAllPidValuesCalculated($parameterIdsInCalculate, $valuesParameters->TimeID)) {
                continue;
            }
    
            $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
            if (!$param) {
                continue;
            }
    
            $result = null;
            $numberBuffer = "";
            $values = [];
            $operatorStack = [];
            $parameters = [];
    
            foreach ($calculateArray as $item) {
                if (strpos($item, 'Pid=') === 0) {
                    $parameterId = substr($item, 4);
                } elseif (strpos($item, 'Tid=') === 0) {
                    $timeId = substr($item, 4);
    
                    $graphicTimeName = DB::table('graphic_times')
                        ->where('id', $timeId)
                        ->value('Name');
    
                    $relatedTimeIds = DB::table('graphic_times')
                        ->where('Name', $graphicTimeName)
                        ->pluck('id');
    
                    $parameters[$parameterId][$timeId] = ValuesParameters::where('ParametersID', $parameterId)
                        ->whereIn('TimeID', $relatedTimeIds)
                        ->where('Created', $valuesParameters->Created)
                        ->value('Value') ?? 0;
                }
            }
    
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
    
                logger()->info("Hisoblash ifodasi: $calculateString");
    
                $result = eval("return ($calculateString);");
    
                if ($result === false) {
                    throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                }
            } catch (\Throwable $e) {
                logger()->error("Hisoblashda xato: " . $e->getMessage());
                continue;
            }
    
            ValuesParameters::withoutEvents(function () use ($valuesParameters, $param, $result) {
                ValuesParameters::updateOrCreate(
                    [
                        'TimeID' => $valuesParameters->TimeID,
                        'ParametersID' => $param->ParametersID,
                        'SourcesID' => $param->SourceID,
                        'Created' => $valuesParameters->Created,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'Value' => round($result, 2),
                        'GraphicsTimesID' => (string) $param->GrapicsID,
                        'BlogID' => (string) $param->BlogsID,
                        'FactoryStructureID' => (string) $param->FactoryStructureID,
                        'ChangeID' => $valuesParameters->ChangeID,
                        'Created' => $valuesParameters->Created,
                        'updated_at' => now(),
                    ]
                );
            });
    
            $this->recalculateDependentFormulas($param->ParametersID, $valuesParameters->TimeID);
        }
    
        // Rekursiya darajasini kamaytirish
        $this->recursionDepth--;
    }
    
    // Pid qiymatlari to‘liq hisoblanganmi, tekshirish
    private function areAllPidValuesCalculated(array $parameterIds, $timeId)
    {
        foreach ($parameterIds as $pid) {
            $value = ValuesParameters::where('ParametersID', $pid)
                ->where('TimeID', $timeId)
                ->value('Value');
    
            if ($value === null || $value === 0) {
                return false;
            }
        }
        return true;
    }
    
    // Rekursiv hisoblashni bajarish
    private function recalculateDependentFormulas($parameterId, $timeId)
    {
        $dependentCalculators = Calculator::where('TimeID', $timeId)->get();
    
        foreach ($dependentCalculators as $depCalculator) {
            $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
            if (!$depCalculateArray) continue;
    
            foreach ($depCalculateArray as $item) {
                if ($item === "Pid={$parameterId}") {
                    $dependentValuesParameters = ValuesParameters::where('ParametersID', $parameterId)
                        ->where('TimeID', $timeId)
                        ->first();
                    if ($dependentValuesParameters) {
                        $this->calculateFormula($dependentValuesParameters);
                    }
                    break;
                }
            }
        }
    }
    
}
