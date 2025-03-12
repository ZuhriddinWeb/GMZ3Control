<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ValuesParametersObserver
{
    /**
     * Handle the ValuesParameters "saved" event.
     */
    public function saved(ValuesParameters $valuesParameters)
    {
        DB::transaction(function () use ($valuesParameters) {
            $this->processCalculators($valuesParameters);
        });
    }

    private function processCalculators(ValuesParameters $valuesParameters)
    {
        // TimeID ga bog‘liq barcha Calculator larni olish
        $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();

        foreach ($calculators as $calculator) {
            $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;

            if (!$calculateArray) {
                continue;
            }

            // `Calculate` ichidagi barcha `Pid` larni olish
            $parameterIdsInCalculate = [];
            foreach ($calculateArray as $item) {
                if (strpos($item, 'Pid=') === 0) {
                    $parameterIdsInCalculate[] = substr($item, 4);
                }
            }

            // Agar mavjud `Pid` lar hali hisoblanmagan bo‘lsa, keyingi siklga o‘tish
            if (!$this->areAllPidValuesCalculated($parameterIdsInCalculate, $valuesParameters->TimeID)) {
                continue;
            }

            // GraphicsParameter ni olish
            $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
            if (!$param) {
                continue;
            }

            // Formulani hisoblash
            $result = $this->calculateFormula($calculateArray, $valuesParameters->TimeID, $valuesParameters->Created);

            // Agar natija null bo‘lsa, qayta ishlash shart emas
            if ($result === null) {
                continue;
            }

            // Natijani saqlash
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

            // Agar natija hisoblangan bo‘lsa, unga bog‘liq formulalarni qayta hisoblash
            $this->recalculateDependentFormulas($param->ParametersID, $valuesParameters->TimeID);
        }
    }

    private function areAllPidValuesCalculated(array $parameterIds, $timeId)
    {
        foreach ($parameterIds as $pid) {
            $value = ValuesParameters::where('ParametersID', $pid)
                ->where('TimeID', $timeId)
                ->value('Value');

            if ($value === null || $value === 0) {
                return false; // Agar bitta ham Pid hali hisoblanmagan bo‘lsa, false qaytarish
            }
        }
        return true;
    }

    private function calculateFormula(array $calculateArray, $timeId, $created)
    {
        $numberBuffer = "";
        $values = [];
        $operatorStack = [];
        $parameters = [];

        foreach ($calculateArray as $item) {
            if (strpos($item, 'Pid=') === 0) {
                $parameterId = substr($item, 4);
            } elseif (strpos($item, 'Tid=') === 0) {
                $timeId = substr($item, 4);

                $graphicTimeName = DB::table('graphic_times')->where('id', $timeId)->value('Name');

                $relatedTimeIds = DB::table('graphic_times')
                    ->where('Name', $graphicTimeName)
                    ->pluck('id');

                $parameters[$parameterId][$timeId] = ValuesParameters::where('ParametersID', $parameterId)
                    ->whereIn('TimeID', $relatedTimeIds)
                    ->where('Created', $created)
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
                return null;
            }

            return eval("return ($calculateString);");
        } catch (\Throwable $e) {
            return null;
        }
    }

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
                        $this->processCalculators($dependentValuesParameters);
                    }
                    break;
                }
            }
        }
    }
}
