<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ValuesParametersObserver
{
    public function saved(ValuesParameters $valuesParameters)
    {
        DB::transaction(function () use ($valuesParameters) {
            $this->calculateFormula($valuesParameters);
        });
    }

    private function calculateFormula(ValuesParameters $valuesParameters)
    {
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

            if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) {
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

            $allPidsHaveValue = true; // **Flag: barcha Pid lar qiymatga ega bo‘lsa `true` bo‘ladi**

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
                        ->value('Value');

                    if (is_null($parameters[$parameterId][$timeId]) || $parameters[$parameterId][$timeId] == 0) {
                        $allPidsHaveValue = false;
                        logger()->warning("❌ **Qiymat yo‘q yoki 0!** ParameterID: $parameterId, TimeID: $timeId");
                    }
                }
            }

            // 🔄 **Agar barcha Pid lar 0 bo‘lmasa, hisoblashni boshlash**
            if (!$allPidsHaveValue) {
                logger()->info("⏳ **Kutish rejimi:** Formuladagi barcha Pid=XXX lar to‘g‘ri natija olmaguncha hisoblanmaydi.");
                return;
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

                logger()->info("🧮 **Hisoblash ifodasi:** $calculateString");

                $result = eval("return ($calculateString);");

                if ($result === false) {
                    throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                }
            } catch (\Throwable $e) {
                logger()->error("❌ **Hisoblashda xato:** " . $e->getMessage());
                return;
            }

            ValuesParameters::withoutEvents(function () use ($valuesParameters, $param, $result) {
                $data = [
                    'ParametersID' => (string) $param->ParametersID,
                    'SourceID' => (string) $param->SourceID,
                    'GTid' => (string) $valuesParameters->TimeID,
                    'Value' => round($result, 2),
                    'GraphicsTimesID' => (string) $param->GrapicsID,
                    'BlogID' => (string) $param->BlogsID,
                    'FactoryStructureID' => (string) $param->FactoryStructureID,
                    'ChangeID' => $valuesParameters->ChangeID,
                    'created_at' => now(),
                    'Created' => $valuesParameters->Created,
                ];
                ValuesParameters::updateOrCreate(
                    [
                        'TimeID' => $data['GTid'],
                        'ParametersID' => $data['ParametersID'],
                        'SourcesID' => $data['SourceID'],
                        'Created' => $valuesParameters->Created,
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
            });

            // 🔄 **Agar natija hisoblangan bo‘lsa, rekursiv ravishda boshqa bog‘liq formulalarni qayta hisoblash**
            $dependentCalculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
            foreach ($dependentCalculators as $depCalculator) {
                $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
                if (!$depCalculateArray) continue;

                foreach ($depCalculateArray as $item) {
                    if ($item === "Pid={$param->ParametersID}") {
                        $dependentValuesParameters = ValuesParameters::where('ParametersID', $param->ParametersID)
                            ->where('TimeID', $valuesParameters->TimeID)
                            ->first();
                        if ($dependentValuesParameters) {
                            logger()->info("🔄 **Rekursiv hisoblash:** ParameterID: {$param->ParametersID}");
                            $this->calculateFormula($dependentValuesParameters);
                        }
                        break;
                    }
                }
            }
        }
    }
}
