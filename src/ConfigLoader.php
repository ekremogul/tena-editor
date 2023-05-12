<?php

namespace Ekremogul\TenaEditor;

class ConfigLoader
{
    public $tools = [];

    public function __construct($configuration)
    {
        if(empty($configuration)) {
            throw new TenaEditorException('Configuration data is empty');
        }

        $config = json_decode($configuration, true);
        $this->loadTools($config);
    }

    private function loadTools($config)
    {
        if(!isset($config['tools'])) {
            throw new TenaEditorException('Tools not found in configuration');
        }
        foreach ($config['tools'] as $toolName => $toolData) {
            if(isset($this->tools[$toolName])) {
                throw new TenaEditorException(sprintf("Duplicate tool %s in configuration", $toolName));
            }
            $this->tools[$toolName] = $this->loadTool($toolData);
        }
    }

    private function loadTool($data)
    {
        return $data;
    }

}
