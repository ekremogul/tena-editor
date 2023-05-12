<?php

namespace Ekremogul\TenaEditor;

class BlockHandler
{
    const DEFAULT_ARRAY_KEY = '-';

    private $rules = null;


    public function __construct($configuration)
    {
        $this->rules = new ConfigLoader($configuration);
    }

    public function validateBlock($blockType, $blockData)
    {
        if(!array_key_exists($blockType, $this->rules->tools)) {
            throw new TenaEditorException(sprintf("Tool %s not found in the configuration", $blockType));
        }

        $rule = $this->rules->tools[$blockType];
        return $this->validate($rule, $blockData);
    }

    public function sanitizeBlock($blockType, $blockData, $blockTunes)
    {
        $rule = $this->rules->tools[$blockType];

        return [
            'type' => $blockType,
            'data' => $this->sanitize($rule, $blockData),
            'tunes' => $blockTunes
        ];
    }

    private function validate($rules, $blockData)
    {
        /**
         * Make sure that every required param exists in data block
         */
        foreach ($rules as $key => $value) {
            if (($key != BlockHandler::DEFAULT_ARRAY_KEY) && (isset($value['required']) ? $value['required'] : true)) {
                if (!isset($blockData[$key])) {
                    throw new EditorJSException("Not found required param `$key`");
                }
            }
        }

        /**
         * Check if there is not extra params (not mentioned in configuration rule)
         */
        foreach ($blockData as $key => $value) {
            if (!is_integer($key) && !isset($rules[$key])) {
                throw new EditorJSException("Found extra param `$key`");
            }
        }

        /**
         * Validate every key in data block
         */
        foreach ($blockData as $key => $value) {
            /**
             * PHP Array has integer keys
             */
            if (is_integer($key)) {
                $key = BlockHandler::DEFAULT_ARRAY_KEY;
            }

            $rule = $rules[$key];

            $rule = $this->expandToolSettings($rule);

            $elementType = $rule['type'];

            /**
             * Process canBeOnly rule
             */
            if (isset($rule['canBeOnly'])) {
                if (!in_array($value, $rule['canBeOnly'])) {
                    throw new EditorJSException("Option '$key' with value `$value` has invalid value. Check canBeOnly param.");
                }

                // Do not perform additional elements validation in any case
                continue;
            }

            /**
             * Do not check element type if it is not required and null
             */
            if (isset($rule['required']) && $rule['required'] === false &&
                isset($rule['allow_null']) && $rule['allow_null'] === true && $value === null) {
                continue;
            }

            /**
             * Validate element types
             */
            switch ($elementType) {
                case 'string':
                    if (!is_string($value)) {
                        throw new EditorJSException("Option '$key' with value `$value` must be string");
                    }
                    break;

                case 'integer':
                case 'int':
                    if (!is_integer($value)) {
                        throw new EditorJSException("Option '$key' with value `$value` must be integer");
                    }
                    break;

                case 'array':
                    $this->validate($rule['data'], $value);
                    break;

                case 'boolean':
                case 'bool':
                    if (!is_bool($value)) {
                        throw new EditorJSException("Option '$key' with value `$value` must be boolean");
                    }
                    break;

                default:
                    throw new EditorJSException("Unhandled type `$elementType`");
            }
        }

        return true;
    }

    private function sanitize($rules, $blockData)
    {
        /**
         * Sanitize every key in data block
         */
        foreach ($blockData as $key => $value) {
            /**
             * PHP Array has integer keys
             */
            if (is_integer($key)) {
                $rule = $rules[BlockHandler::DEFAULT_ARRAY_KEY];
            } else {
                $rule = $rules[$key];
            }

            $rule = $this->expandToolSettings($rule);
            $elementType = $rule['type'];

            /**
             * Sanitize string with Purifier
             */
            if ($elementType == 'string') {
                $allowedTags = isset($rule['allowedTags']) ? $rule['allowedTags'] : '';
                if ($allowedTags !== '*') {
                    $blockData[$key] = $this->getPurifier($allowedTags)->purify($value);
                }
            }

            /**
             * Sanitize nested elements
             */
            if ($elementType == 'array') {
                $blockData[$key] = $this->sanitize($rule['data'], $value);
            }
        }

        return $blockData;
    }
    private function getPurifier($allowedTags)
    {
        $sanitizer = $this->getDefaultPurifier();

        $sanitizer->set('HTML.Allowed', $allowedTags);

        /**
         * Define custom HTML Definition for mark tool
         */
        if ($def = $sanitizer->maybeGetRawHTMLDefinition()) {
            $def->addElement('mark', 'Inline', 'Inline', 'Common');
        }

        $purifier = new \HTMLPurifier($sanitizer);

        return $purifier;
    }

    private function getDefaultPurifier()
    {
        $sanitizer = \HTMLPurifier_Config::createDefault();

        $sanitizer->set('HTML.TargetBlank', true);
        $sanitizer->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $sanitizer->set('AutoFormat.RemoveEmpty', true);
        $sanitizer->set('HTML.DefinitionID', 'html5-definitions');

        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'purifier';
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        $sanitizer->set('Cache.SerializerPath', $cacheDirectory);

        return $sanitizer;
    }

    private function isAssoc(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function expandToolSettings($rule)
    {
        if (is_string($rule)) {
            // 'blockName': 'string' – tool with string type and default settings
            $expandedRule = ["type" => $rule];
        } elseif (is_array($rule)) {
            if ($this->isAssoc($rule)) {
                $expandedRule = $rule;
            } else {
                // 'blockName': [] – tool with canBeOnly and default settings
                $expandedRule = ["type" => "string", "canBeOnly" => $rule];
            }
        } else {
            throw new EditorJSException("Cannot determine element type of the rule `$rule`.");
        }

        return $expandedRule;
    }
}
