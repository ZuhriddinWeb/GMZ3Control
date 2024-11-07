<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Http\Request;
use App\Http\Controllers\api\ParametrValueController;

class ValuesParametersObserver
{
    /**
     * Handle the ValuesParameters "created" event.
     */
    public function saved(ValuesParameters $valuesParameters)
    {
        // Load calculators for the specified TimeID
        $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
    
        // Get the relevant calculator entry
        $calculator = $this->findRelevantCalculator($calculators, $valuesParameters);
    
        if (!$calculator) {
            return response()->json(['error' => 'Calculator entry not found'], 404);
        }
    
        $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
        if (!$param) {
            return response()->json(['error' => 'Graphics parameter entry not found'], 404);
        }
    
        // Extract and process calculate array values
        $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;
        [$parameters, $operators] = $this->extractParametersOperators($calculateArray);
    
        // Fetch parameter values
        $parameterValues = $this->fetchParameterValues($parameters);
    
        if (empty($parameterValues)) {
            return response()->json(['error' => 'No parameter values found'], 404);
        }
    
        // Prepare and evaluate the calculation string
        $calculateString = $this->buildCalculationString($calculateArray, $parameterValues, $operators);
        $result = $this->evaluateCalculation($calculateString);
    
        // Prepare data for storage
        $data = $this->prepareData($valuesParameters, $param, $result);
    
        // Save or update the result in ValuesParameters table
        return $this->saveOrUpdateValue($data);
    }
    
    private function findRelevantCalculator($calculators, $valuesParameters)
    {
        return $calculators->firstWhere(function ($calc) use ($valuesParameters) {
            $calculateArray = is_string($calc->Calculate) ? json_decode($calc->Calculate, true) : $calc->Calculate;
            return is_array($calculateArray) &&
                   in_array("Pid={$valuesParameters->ParametersID}", $calculateArray);
        });
    }
    
    private function extractParametersOperators($calculateArray)
    {
        $parameterIds = [];
        $operators = [];
    
        foreach ($calculateArray as $item) {
            if (strpos($item, 'Pid=') === 0) {
                $parameterIds[] = substr($item, 4);
            } elseif (strpos($item, 'Tid=') === 0) {
                // Tid is only processed if specific logic for TimeID is required
                // Placeholder to manage Tid if needed later
            } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                $operators[] = $item;
            }
        }
    
        return [$parameterIds, $operators];
    }
    
    private function fetchParameterValues($parameterIds)
    {
        $parameters = [];
    
        foreach ($parameterIds as $parameterId) {
            $parameterValue = ValuesParameters::where('ParametersID', $parameterId)->value('Value');
            if ($parameterValue !== null) {
                $parameters[$parameterId] = $parameterValue;
            }
        }
    
        return $parameters;
    }
    
    private function buildCalculationString($calculateArray, $parameterValues, $operators)
    {
        $values = [];
        $numberBuffer = "";
    
        foreach ($calculateArray as $item) {
            if (strpos($item, 'Pid=') === 0) {
                $parameterId = substr($item, 4);
                $value = $parameterValues[$parameterId] ?? 0;
                $numberBuffer .= (string)$value;
            } elseif (strpos($item, 'Tid=') === 0) {
                // Tid can be handled if needed, currently not added to numberBuffer
            } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                if ($numberBuffer !== "") {
                    $values[] = $numberBuffer;
                    $numberBuffer = "";
                }
                if ($item === '=') break;
                $values[] = $item;
            } else {
                $numberBuffer .= $item;
            }
        }
    
        if ($numberBuffer !== "") {
            $values[] = $numberBuffer;
        }
    
        return implode(' ', $values);
    }
    
    private function evaluateCalculation($calculateString)
    {
        try {
            return eval("return $calculateString;");
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Calculation error: ' . $e->getMessage()], 500);
        }
    }
    
    private function prepareData($valuesParameters, $param, $result)
    {
        return [
            'ParametersID' => (string) $param->ParametersID,
            'SourceID' => (string) $param->SourceID,
            'GTid' => (string) $valuesParameters->TimeID,
            'Value' => round($result, 2),
            'GraphicsTimesID' => (string) $param->GrapicsID,
            'BlogID' => (string) $param->BlogsID,
            'FactoryStructureID' => (string) $param->FactoryStructureID,
            'created_at' => now(),
            'Created' => now(),
        ];
    }
    
    private function saveOrUpdateValue($data)
    {
        $existingValue = ValuesParameters::where([
            'TimeID' => $data['GTid'],
            'ParametersID' => $data['ParametersID'],
            'SourcesID' => $data['SourceID'],
        ])->first();
    
        if ($existingValue) {
            $existingValue->update([
                'Value' => $data['Value'],
                'GraphicsTimesID' => $data['GraphicsTimesID'],
                'BlogID' => $data['BlogID'],
                'FactoryStructureID' => $data['FactoryStructureID'],
                'updated_at' => now(),
            ]);
    
            return response()->json(['message' => 'Value updated successfully']);
        } else {
            $request = new Request($data);
            $parameterValueController = new ParametrValueController();
            return $parameterValueController->create($request);
        }
    }
    
    // private function calculateWithPrecedence(array $values)
    // {
    //     // Initialize the result with the first numeric value
    //     $result = array_shift($values); // Take the first value and remove it from the array

    //     while (!empty($values)) {
    //         $currentOperator = array_shift($values); // Get the next operator
    //         $nextValue = array_shift($values); // Get the next numeric value

    //         if (is_numeric($nextValue)) { // Ensure nextValue is numeric
    //             switch ($currentOperator) {
    //                 case '+':
    //                     $result += $nextValue;
    //                     break;
    //                 case '-':
    //                     $result -= $nextValue;
    //                     break;
    //                 case '*':
    //                     $result *= $nextValue;
    //                     break;
    //                 case '÷':
    //                 case '/':
    //                     $result /= ($nextValue != 0) ? $nextValue : 1; // Avoid division by zero
    //                     break;
    //                 default:
    //                     break; // Handle any unexpected operators
    //             }
    //         }
    //     }

    //     return $result; // Return the calculated result
    // }
    /**
     * Handle the ValuesParameters "deleted" event.
     */
    public function deleted(ValuesParameters $valuesParameters): void
    {
        //
    }

    /**
     * Handle the ValuesParameters "restored" event.
     */
    public function restored(ValuesParameters $valuesParameters): void
    {
        //
    }

    /**
     * Handle the ValuesParameters "force deleted" event.
     */
    public function forceDeleted(ValuesParameters $valuesParameters): void
    {
        //
    }
}
