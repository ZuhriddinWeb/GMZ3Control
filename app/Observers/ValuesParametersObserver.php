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
        // dd($valuesParameters->TimeID);
        $calculator = Calculator::whereJsonContains('Calculate', $valuesParameters->ParametersID)->first();
        $param = GraphicsParamenters::where('ParametersID',$calculator->ParametersID)->first();
        // dd($param);

        if (!$calculator) {
            return response()->json(['error' => 'Calculator entry not found'], 404);
        }
    
        // Step 2: Directly access the 'Calculate' field as an array
        $calculateArray = $calculator->Calculate;
    
        // Step 3: Extract the parameter IDs from the 'Calculate' array
        $parameterIds = [];
        $operators = [];
    
        foreach ($calculateArray as $item) {
            if (strlen($item) === 36) {
                // This is likely a ParametersID
                $parameterIds[] = $item;
            } elseif (in_array($item, ['+', '-', '*', '÷', '/'])) {
                // This is an arithmetic operator
                $operators[] = $item;
            }
        }
    
        // Check if we have any ParametersID to query
        if (empty($parameterIds)) {
            return response()->json(['error' => 'No valid ParametersID found in Calculate field'], 400);
        }
        $timeId =   ValuesParameters::whereIn('ParametersID', $parameterIds)->get()->pluck('TimeID', )->toArray();
        // dd($timeId[0]);
        // Step 4: Query the parameters_value table using the extracted ParametersID values
        $parameters = ValuesParameters::whereIn('ParametersID', $parameterIds)->get()->pluck('Value', 'ParametersID')->toArray();
        
        if (empty($parameters)) {
            return response()->json(['error' => 'No parameter values found'], 404);
        }
    
        // Initialize variables for calculation
        $result = null;
        $currentOperator = null;
        $numberBuffer = "";
        $values = [];
    
        // Parse the calculation array
        foreach ($calculateArray as $item) {
            if (strlen($item) === 36) {
                // This is a ParametersID, retrieve its value
                $value = $parameters[$item] ?? 0; // Use 0 if the parameter is missing
    
                // Concatenate the value to the buffer
                $numberBuffer .= (string)$value; // Ensure it's a string
            } elseif (in_array($item, ['+', '-', '*', '÷', '/', '='])) {
                // If we have a buffered number, convert it to a float and store it
                if ($numberBuffer !== "") {
                    $values[] = (float)$numberBuffer; // Store the buffered number as float
                    $numberBuffer = ""; // Reset the buffer
                }
    
                if ($item === '=') {
                    break; // End of calculation
                } else {
                    // Store the operator
                    $values[] = $item; 
                }
            } else {
                // Concatenate number values
                $numberBuffer .= $item; // Concatenate numbers
            }
        }
    
        // Handle the last buffered number if any
        if ($numberBuffer !== "") {
            $values[] = (float)$numberBuffer; // Store the last number as float
        }
    
        // Perform calculations respecting operator precedence
        if (empty($values)) {
            return null; // No values to calculate
        }
    
        // Call the calculation function with precedence handling
        $result = $this->calculateWithPrecedence($values);
        $data = [
            'ParametersID' => (string)$param->ParametersID,
            'SourceID' => (string)$param->SourceID,
            'GTid' => (string)$valuesParameters->TimeID,
            'Value' => round($result, 2),
            'GraphicsTimesID' => (string)$param->GrapicsID,
            'BlogID' => (string)$param->BlogsID,
            'FactoryStructureID' => (string)$param->FactoryStructureID,
            'created_at' => now(),
            'Created' => now(),
        ];
        $existingValue = ValuesParameters::where([
            'TimeID' => $data['GTid'],
            'ParametersID' => $data['ParametersID'],
            'SourcesID' => $data['SourceID'],
        ])->first();
        if ($existingValue) {
            // dd($existingValue);
            // Update the existing record
            $existingValue->update([
                'Value' => $data['Value'],
                'GraphicsTimesID' => $data['GraphicsTimesID'],
                'BlogID' => $data['BlogID'],
                'FactoryStructureID' => $data['FactoryStructureID'],
                'updated_at' => now(),
            ]);
            
            return response()->json(['message' => 'Value updated successfully']);
        } else {
            // Create a new record
            $request = new Request($data);
            $parameterValueController = new ParametrValueController();
            return $parameterValueController->create($request);
        }
        // dd($result);
        
    }
    private function calculateWithPrecedence(array $values)
    {
        // Initialize the result with the first numeric value
        $result = array_shift($values); // Take the first value and remove it from the array
    
        while (!empty($values)) {
            $currentOperator = array_shift($values); // Get the next operator
            $nextValue = array_shift($values); // Get the next numeric value
    
            if (is_numeric($nextValue)) { // Ensure nextValue is numeric
                switch ($currentOperator) {
                    case '+':
                        $result += $nextValue;
                        break;
                    case '-':
                        $result -= $nextValue;
                        break;
                    case '*':
                        $result *= $nextValue;
                        break;
                    case '÷':
                    case '/':
                        $result /= ($nextValue != 0) ? $nextValue : 1; // Avoid division by zero
                        break;
                    default:
                        break; // Handle any unexpected operators
                }
            }
        }
    
        return $result; // Return the calculated result
    }
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
