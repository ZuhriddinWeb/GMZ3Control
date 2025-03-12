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
            $this->calculateAndSave($valuesParameters);
        });
    }

    /**
     * **Rekursiv hisoblash va saqlash**
     */
    private function calculateAndSave(ValuesParameters $valuesParameters)
    {
        // TimeID bo‘yicha Calculator larni olish
        $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();

        foreach ($calculators as $calculator) {
            $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;

            if (!$calculateArray) {
                continue;
            }

            // **Calculate ichidagi barcha Pid larni olish**
            $parameterIdsInCalculate = [];
            foreach ($calculateArray as $item) {
                if (strpos($item, 'Pid=') === 0) {
                    $parameterIdsInCalculate[] = substr($item, 4);
                }
            }

            if (empty($parameterIdsInCalculate)) {
                continue;
            }

            // **Agar ValuesParameters->ParametersID Pid lar ichida bo‘lsa, hisoblashni boshlaymiz**
            if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) {
                continue;
            }

            // **GraphicsParameter yozuvini olish**
            $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
            if (!$param) {
                continue;
            }

            // **Hisoblash uchun kerakli qiymatlarni olish**
            $parameters = [];
            foreach ($calculateArray as $item) {
                if (strpos($item, 'Pid=') === 0) {
                    $parameterId = substr($item, 4);
                } elseif (strpos($item, 'Tid=') === 0) {
                    $timeId = substr($item, 4);

                    // **Graphic Times dan `Name` ni olish**
                    $graphicTimeName = DB::table('graphic_times')
                        ->where('id', $timeId)
                        ->value('Name');

                    if (!$graphicTimeName) {
                        logger()->error("Graphic Times Name topilmadi! TimeID: $timeId");
                        continue;
                    }

                    // **Shu `Name` bilan bog‘liq barcha `id` larni olish**
                    $relatedTimeIds = DB::table('graphic_times')
                        ->where('Name', $graphicTimeName)
                        ->pluck('id');

                    // **ValuesParameters dan qiymat olish**
                    $parameters[$parameterId][$timeId] = $parameters[$parameterId][$timeId] ??
                        ValuesParameters::where('ParametersID', $parameterId)
                            ->whereIn('TimeID', $relatedTimeIds)
                            ->where('Created', $valuesParameters->Created)
                            ->value('Value') ?? 0;

                    // **Log orqali tekshirish**
                    logger()->info("TimeID: $timeId, Graphic Time Name: $graphicTimeName, Related TimeIDs: " . implode(',', $relatedTimeIds->toArray()));
                }
            }

            // **Hisoblash ifodasini yaratish**
            $values = [];
            $numberBuffer = "";
            $operatorStack = [];

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

            // **Hisoblash ifodasini bajarish**
            $calculateString = implode(' ', $values);
            try {
                if (empty($calculateString)) {
                    throw new \Exception("Bo‘sh matematik ifoda!");
                }

                // Debug: Ifoda qanday chiqayotganini tekshirish
                logger()->info("Hisoblash ifodasi: $calculateString");

                $result = eval("return ($calculateString);");

                if ($result === false) {
                    throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                }
            } catch (\Throwable $e) {
                logger()->error("Hisoblashda xato: " . $e->getMessage());
                continue;
            }

            // **Natijani saqlash**
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

            // **Rekursiv hisoblash**
            $dependentValuesParameters = ValuesParameters::where('ParametersID', $param->ParametersID)
                ->where('TimeID', $valuesParameters->TimeID)
                ->first();
            if ($dependentValuesParameters) {
                $this->calculateAndSave($dependentValuesParameters);
            }
        }
    }
}
