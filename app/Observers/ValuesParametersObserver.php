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

                if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) {
                    continue;
                }

                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) continue;

                $parameters = [];
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
                        $parameters[$parameterId][$timeId] = $parameters[$parameterId][$timeId] ?? 
                            ValuesParameters::where('ParametersID', $parameterId)
                                ->where('TimeID', $timeId)
                                ->where('Created', $valuesParameters->Created)
                                ->value('Value') ?? 0;
                    }
                }

                $numberBuffer = "";
                $values = [];
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
                        $values[] = $item;
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

                $this->recalculateDependentFormulas($param->ParametersID, $valuesParameters->TimeID, $valuesParameters->Created);
            }
        });
    }

    private function recalculateDependentFormulas($parametersID, $timeID, $created)
    {
        $dependentCalculators = Calculator::where('TimeID', $timeID)->get();

        foreach ($dependentCalculators as $depCalculator) {
            $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
            if (!$depCalculateArray) continue;

            foreach ($depCalculateArray as $item) {
                $cleanItem = trim($item, ' ,');
                if (strpos($cleanItem, "Pid=") === 0) {
                    $pid = substr($cleanItem, 4);
                    if ($pid === $parametersID) {
                        $dependentValuesParameters = ValuesParameters::where('ParametersID', $depCalculator->ParametersID)
                            ->where('TimeID', $timeID)
                            ->where('Created', $created)
                            ->first();

                        if ($dependentValuesParameters) {
                            $this->saved($dependentValuesParameters);
                            $this->recalculateDependentFormulas($depCalculator->ParametersID, $timeID, $created);
                        }
                        break;
                    }
                }
            }
        }
    }
}
