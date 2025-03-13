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
        // dd($valuesParameters);
        DB::transaction(function () use ($valuesParameters) {
            // TimeID bir xil bo‘lgan barcha mos calculator yozuvlarini olish
            $calculators = DB::table('calculators')
                ->join('graphic_times', 'calculators.TimeID', '=', 'graphic_times.id')
                ->where('graphic_times.Name', $valuesParameters->TimeStr) // TimeStr bilan Name solishtirish
                ->select('calculators.*')
                ->get();

            // dd($calculators);
            // Har bir calculator uchun ishga tushirish
            foreach ($calculators as $calculator) {
                $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;

                // 🛑 **DEBUG: Agar Calculate maydoni bo‘sh bo‘lsa, tekshirish**
                if (!$calculateArray) {
                    // dd("ERROR: Calculate bo‘sh!", ['CalculatorID' => $calculator->id, 'Calculate' => $calculator->Calculate]);
                    continue;
                }

                // **2️⃣ Calculate ichidagi barcha Pid larni olish**
                $parameterIdsInCalculate = [];
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterIdsInCalculate[] = substr($item, 4); // `Pid=` ni olib tashlab, ID ni olish
                    }
                }

                // 🛑 **DEBUG: Agar Pid lar bo‘sh bo‘lsa, tekshirish**
                if (empty($parameterIdsInCalculate)) {
                    // dd("ERROR: Calculate ichidan hech qanday Pid topilmadi!", ['CalculatorID' => $calculator->id, 'CalculateArray' => $calculateArray]);
                    continue;
                }

                // **3️⃣ Agar ValuesParameters->ParametersID shu Pid lar orasida bo'lsa, ushbu Calculator ni ishlatamiz**
                if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) {
                    continue; // Agar ParametersID Calculate ichidagi Pid lar orasida bo'lmasa, keyingi siklga o'tish
                }

                // **4️⃣ GraphicsParameter yozuvini olish**
                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) {
                    // dd("ERROR: GraphicsParameter topilmadi!", ['CalculatorID' => $calculator->id, 'ParametersID' => $calculator->ParametersID]);
                    continue;
                }

                // **✅ Test natijani ko'rish uchun**
                // dd([
                //     'calculator_id' => $calculator->id,
                //     'parameters_in_calculate' => $parameterIdsInCalculate,
                //     'selected_parameters_id' => $valuesParameters->ParametersID,
                //     'selected_param' => $param
                // ]);
                // O‘zgaruvchilarni ishga tushirish
                $result = null;
                $numberBuffer = "";
                $values = [];
                $operatorStack = [];
                $parameters = [];

                // `Calculate` ichidagi har bir `ParametersID` va `TimeID` ni `ValuesParameters` jadvalidan olish
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);

                        // 🔹 `graphic_times` dan `TimeStr` ni olish
                        $graphicTimeName = DB::table('graphic_times')->where('id', $timeId)->value('Name');

                        // 🔹 `values_parameters` dan TimeStr orqali qiymatni olish
                        $parameters[$parameterId][$timeId] = ValuesParameters::where('ParametersID', $parameterId)
                            ->where('TimeStr', $graphicTimeName)
                            ->where('Created', $valuesParameters->Created)
                            ->value('Value') ?? 0;
                    }
                }

                // Hisoblash ifodasini yaratish uchun `calculateArray` ichidagi har bir elementni ko‘rib chiqish
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

                // Hisoblash ifodasini birlashtirish
                $calculateString = implode(' ', $values);

                try {
                    if (empty($calculateString)) {
                        throw new \Exception("Bo‘sh matematik ifoda!");
                    }

                    // Debug: Ifoda qanday chiqayotganini tekshirish
                    logger()->info("Hisoblash ifodasi: $calculateString");

                    $result = eval ("return ($calculateString);");

                    if ($result === false) {
                        throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                    }
                } catch (\Throwable $e) {
                    logger()->error("Hisoblashda xato: " . $e->getMessage());
                    continue;
                }



                // Ma'lumotlarni qo‘shish yoki yangilashni hodisalarsiz amalga oshirish
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
                            // 'TimeID' => $data['GTid'],
                            'ParametersID' => $data['ParametersID'],
                            'SourcesID' => $data['SourceID'],
                            'Created' => $valuesParameters->Created,
                            'TimeStr' => $data['TimeStr'],
                        ],
                        [
                            'id' => (string) Str::uuid(), // UUID ni qo'shish
                            'Value' => $data['Value'],
                            'TimeID' => $data['GTid'],
                            'GraphicsTimesID' => $data['GraphicsTimesID'],
                            'BlogID' => $data['BlogID'],
                            'FactoryStructureID' => $data['FactoryStructureID'],
                            'ChangeID' => $valuesParameters->ChangeID,
                            'Created' => $valuesParameters->Created,
                            'updated_at' => now(),
                        ]
                    );

                    // Tekshirish: Natija bazaga to'g'ri yozilganligini ko'rish uchun
                    logger()->info("Bazaga yozilgan yozuv: ", $newOrUpdateRecord->toArray());
                    // dd($newOrUpdateRecord); // Agar kerak bo'lsa, bu qator natijani tekshirish uchun ishlatilishi mumkin
                });
                // **🔄 Natija bog‘liq bo‘lgan boshqa formulalarda ishlatilsa, ularni ham qayta hisoblash**
                // 🔹 Calculator dagi bog‘liq formulalarni olish
                $dependentCalculators = DB::table('calculators')
                    ->join('graphic_times', 'calculators.TimeID', '=', 'graphic_times.id')
                    ->where('graphic_times.Name', $valuesParameters->TimeStr)
                    ->select('calculators.*')
                    ->get();
                    
                foreach ($dependentCalculators as $depCalculator) {
                    $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
                    if (!$depCalculateArray)
                        continue;

                    foreach ($depCalculateArray as $item) {
                        if ($item === "Pid={$param->ParametersID}") {
                            // 🔎 Asl Calculator dagi TimeID ni olish
                            $calculatorTimeID = $depCalculator->TimeID;

                            // 🗃️ Bog‘liq parametrlarni olish
                            $dependentValuesParameters = ValuesParameters::where('ParametersID', $param->ParametersID)
                                ->where('TimeID', $calculatorTimeID) // 🔹 Faqat Calculator dagi TimeID ishlatiladi!
                                ->where('Created', $valuesParameters->Created)
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
