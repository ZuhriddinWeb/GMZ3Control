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

                if (empty($parameterIdsInCalculate)) {
                    continue;
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
                $missingParameters = false;

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

                        $paramValue = ValuesParameters::where('ParametersID', $parameterId)
                            ->whereIn('TimeID', $relatedTimeIds)
                            ->where('Created', $valuesParameters->Created)
                            ->value('Value');

                        if (is_null($paramValue)) {
                            $missingParameters = true;
                        }

                        $parameters[$parameterId][$timeId] = $paramValue ?? 0;
                    }
                }

                // **Agar hisoblash uchun barcha parametrlar mavjud bo‘lmasa, bu formulani keyinroq qayta hisoblash kerak**
                if ($missingParameters) {
                    Log::warning("Formula hali to‘liq hisoblanmadi: $calculator->id. Qayta hisoblashga qo‘shilmoqda.");
                    return; // Keyinchalik bu observer yana ishga tushadi
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

                    Log::info("Hisoblash ifodasi: $calculateString");

                    $result = eval ("return ($calculateString);");

                    if ($result === false) {
                        throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                    }
                } catch (\Throwable $e) {
                    Log::error("Hisoblashda xato: " . $e->getMessage());
                    continue;
                }

                ValuesParameters::updateOrCreate(
                    [
                        'TimeID' => $valuesParameters->TimeID,
                        'ParametersID' => $param->ParametersID,
                        'SourcesID' => $param->SourceID,
                        'Created' => $valuesParameters->Created,
                    ],
                    ['id' => (string) Str::uuid(), 'Value' => $data['Value'], 'GraphicsTimesID' => $data['GraphicsTimesID'], 'BlogID' => $data['BlogID'], 'FactoryStructureID' => $data['FactoryStructureID'], 'ChangeID' => $valuesParameters->ChangeID, 'Created' => $valuesParameters->Created, 'updated_at' => now(),]
                );

                Log::info("Bazaga yozilgan natija: " . round($result, 2));

                // **🔄 Bog‘liq formulalarni qayta hisoblash (REKURSIYA)**
                $dependentCalculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
                foreach ($dependentCalculators as $depCalculator) {
                    $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
                    if (!$depCalculateArray)
                        continue;

                    foreach ($depCalculateArray as $item) {
                        if ($item === "Pid={$param->ParametersID}") {
                            $dependentValuesParameters = ValuesParameters::where('ParametersID', $param->ParametersID)
                                ->whereIn('TimeID', $relatedTimeIds)
                                ->first();

                            if ($dependentValuesParameters) {
                                $this->saved($dependentValuesParameters); // **REKURSIYA: Agar yangi hisoblangan natija boshqa formulaga bog‘liq bo‘lsa, qayta hisoblash**
                            }
                            break;
                        }
                    }
                }
            }
        });
    }
}
