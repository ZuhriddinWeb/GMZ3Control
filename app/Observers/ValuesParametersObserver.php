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
            // TimeID bir xil bo‘lgan barcha mos calculator yozuvlarini olish
            $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
            // Har bir calculator uchun ishga tushirish
            foreach ($calculators as $calculator) {
                // Ushbu ParametersID uchun GraphicsParameter yozuvini olish
                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) {
                    continue; // Agar mos keluvchi GraphicsParameter topilmasa, keyingi siklga o'tish
                }

                // `Calculate` maydonini JSON stringdan massivga aylantirish, agar u string bo'lsa
                $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;

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

                        // Har bir unikal Tid uchun parametr qiymatini olish
                        $parameters[$parameterId][$timeId] = $parameters[$parameterId][$timeId] ?? 
                            ValuesParameters::where('ParametersID', $parameterId)
                            ->join('')
                            ->where('TimeID', $timeId)
                            ->where('Created',$valuesParameters->Created)
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
                    // Ifodani hisoblash
                    $result = eval("return $calculateString;");
                    // logger()->info("Hisoblangan natija: $result"); // Natijani logga yozish
                } catch (\Exception $e) {
                    // logger()->error("Hisoblashda xato: " . $e->getMessage());
                    continue;
                }

                // Ma'lumotlarni qo‘shish yoki yangilashni hodisalarsiz amalga oshirish
                ValuesParameters::withoutEvents(function () use ($valuesParameters, $param, $result) {
                    $data = [
                        'ParametersID' => (string) $param->ParametersID,
                        'SourceID' => (string) $param->SourceID,
                        'GTid' => (string) $valuesParameters->TimeID,
                        'Value' => round($result, 2),
                        'GraphicsTimesID' => (string) $param->GrapicsID,
                        'BlogID' => (string) $param->BlogsID,
                        'FactoryStructureID' => (string) $param->FactoryStructureID,
                        'ChangeID'=>$valuesParameters->ChangeID,
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
                            'id' => (string) Str::uuid(), // UUID ni qo'shish
                            'Value' => $data['Value'],
                            'GraphicsTimesID' => $data['GraphicsTimesID'],
                            'BlogID' => $data['BlogID'],
                            'FactoryStructureID' => $data['FactoryStructureID'],
                            'ChangeID'=>$valuesParameters->ChangeID,
                            'Created' => $valuesParameters->Created,
                            'updated_at' => now(),
                        ]
                    );

                    // Tekshirish: Natija bazaga to'g'ri yozilganligini ko'rish uchun
                    // logger()->info("Bazaga yozilgan yozuv: ", $newOrUpdateRecord->toArray());
                    // dd($newOrUpdateRecord); // Agar kerak bo'lsa, bu qator natijani tekshirish uchun ishlatilishi mumkin
                });
            }
        });
    }
}
