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
    
                // **1️⃣ Calculate ichidagi barcha Pid va Tid qiymatlarni olish**
                $parameters = [];
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
    
                        // **2️⃣ ValuesParameters jadvalidan Pid va Tid bo‘yicha qiymatni olish**
                        $parameters[$parameterId][$timeId] = $parameters[$parameterId][$timeId] ??
                            ValuesParameters::where('ParametersID', $parameterId)
                                ->where('TimeID', $timeId)
                                ->where('Created', $valuesParameters->Created)
                                ->value('Value') ?? 0;
                    }
                }
    
                // **3️⃣ Formula ichidagi matematik ifodani yaratish**
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
    
                // **4️⃣ Matematik ifodani hisoblash**
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
    
                // **5️⃣ Natijani saqlash**
                ValuesParameters::withoutEvents(function () use ($valuesParameters, $calculator, $result) {
                    ValuesParameters::updateOrCreate(
                        [
                            'TimeID' => $valuesParameters->TimeID,
                            'ParametersID' => $calculator->ParametersID,
                            'SourcesID' => $valuesParameters->SourcesID,
                            'Created' => $valuesParameters->Created,
                        ],
                        [
                            'Value' => round($result, 2),
                            'updated_at' => now(),
                        ]
                    );
                });
    
                // **6️⃣ Natija bog‘liq bo‘lgan boshqa formulalarda ishlatilsa, ularni ham qayta hisoblash**
                $this->recalculateDependentFormulas($calculator->ParametersID, $valuesParameters->TimeID);
            }
        });
    }
    
    /**
     * **🔄 Hisoblangan natijaga bog‘liq bo‘lgan formulalarni qayta hisoblash**
     */
    private function recalculateDependentFormulas($updatedParameterID, $timeID)
    {
        $dependentCalculators = Calculator::where('TimeID', $timeID)->get();
    
        foreach ($dependentCalculators as $calculator) {
            $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;
            if (!$calculateArray) continue;
    
            foreach ($calculateArray as $item) {
                if ($item === "Pid={$updatedParameterID}") {
                    $valuesParameters = ValuesParameters::where('ParametersID', $updatedParameterID)
                        ->where('TimeID', $timeID)
                        ->first();
                    if ($valuesParameters) {
                        $this->saved($valuesParameters);
                    }
                    break;
                }
            }
        }
    }
    
}
