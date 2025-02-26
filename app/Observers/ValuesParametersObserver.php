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

                if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) {
                    continue;
                }

                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) continue;

                $parameters = [];
                foreach ($parameterIdsInCalculate as $parameterId) {
                    foreach ($calculateArray as $item) {
                        if (strpos($item, 'Tid=') === 0) {
                            $timeId = substr($item, 4);
                            $parameters[$parameterId][$timeId] = $parameters[$parameterId][$timeId] ?? 
                                ValuesParameters::where('ParametersID', $parameterId)
                                ->where('TimeID', $timeId)
                                ->where('Created', $valuesParameters->Created)
                                ->value('Value') ?? 0;
                        }
                    }
                }

                $numberBuffer = "";
                $values = [];
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        // ❌ Tid= elementlarini hisoblashdan olib tashlash
                        continue;
                    } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                        if ($numberBuffer !== "") {
                            $values[] = $numberBuffer;
                            $numberBuffer = "";
                        }
                        if ($item === '÷') {
                            $item = '/';
                        }
                        $values[] = $item;
                    } else {
                        $numberBuffer .= $item;
                    }
                }

                if ($numberBuffer !== "") {
                    $values[] = $numberBuffer;
                }
                
                // Hisoblash ifodasini birlashtirish
                $calculateString = implode(' ', $values);
                
                // **🔹 Agar `Tid=` so‘zlari hali ham qoldi deb o‘ylasangiz, ularni tozalash:**
                $calculateString = preg_replace('/Tid=\d+/', '', $calculateString);

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
                    $newOrUpdateRecord = ValuesParameters::updateOrCreate(
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

                // **🔄 Natija bog‘liq bo‘lgan boshqa formulalarda ishlatilsa, ularni ham qayta hisoblash**
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
