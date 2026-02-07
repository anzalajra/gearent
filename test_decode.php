<?php

$json_int_keys = '{"1":"Monday","2":"Tuesday"}';
$decoded_int_keys = json_decode($json_int_keys, true);

echo "Decoded (Int Keys):\n";
var_dump($decoded_int_keys);

$keys = array_keys($decoded_int_keys);
echo "Keys type: " . gettype($keys[0]) . "\n";

$json_list = '["1","2","3"]';
$decoded_list = json_decode($json_list, true);
echo "Decoded List:\n";
var_dump($decoded_list);

// Filament CheckboxList stores KEYS. If keys are '1', '2' in the options array.
// If options are defined as ['1' => 'Monday'], PHP casts '1' to integer 1.
// So json_encode([1, 2]) -> "[1,2]"

$options = [
    '1' => 'Monday',
    '0' => 'Sunday'
];
echo "Options Keys:\n";
var_dump(array_keys($options)); // Expect integers

// If Filament saves keys:
$saved = [1, 2]; 
$json_saved = json_encode($saved);
echo "Saved JSON: $json_saved\n";

$decoded_saved = json_decode($json_saved, true);
echo "Decoded Saved:\n";
var_dump($decoded_saved); // Expect integers

// JS includes check
// If JS array is [1, 2]
// date.getDay() returns 1 (number). .toString() returns "1" (string).
// [1, 2].includes("1") -> FALSE in JS?
// Let's test that hypothesis in node if needed, but I know includes is strict.
