<?php

namespace Ekremogul\TenaEditor;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class TenaEditor
{
    public $blocks = [];

    public $config;

    public $handler;

    public function __construct($json, $configuration)
    {
        $this->handler = new BlockHandler($configuration);

        /**
         * Check if json string is empty
         */
        if (empty($json)) {
            throw new EditorJSException('JSON is empty');
        }

        /**
         * Check input data
         */
        $data = json_decode($json, true);

        /**
         * Handle decoding JSON error
         */
        if (json_last_error()) {
            throw new EditorJSException('Wrong JSON format: ' . json_last_error_msg());
        }

        /**
         * Check if data is null
         */
        if (is_null($data)) {
            throw new EditorJSException('Input is null');
        }

        /**
         * Count elements in data array
         */
        if (count($data) === 0) {
            throw new EditorJSException('Input array is empty');
        }

        /**
         * Check if blocks param is missing in data
         */
        if (!isset($data['blocks'])) {
            throw new EditorJSException('Field `blocks` is missing');
        }


        if (!is_array($data['blocks'])) {
            throw new EditorJSException('Blocks is not an array');
        }

        foreach ($data['blocks'] as $blockData) {
            if (is_array($blockData)) {
                array_push($this->blocks, $blockData);
            } else {
                throw new EditorJSException('Block must be an Array');
            }
        }

        /**
         * Validate blocks structure
         */
        $this->validateBlocks();
    }
    public function getBlocks()
    {
        $sanitizedBlocks = [];

        foreach ($this->blocks as $block) {
            $sanitizedBlock = $this->handler->sanitizeBlock(
                $block['type'],
                $block['data'],
                $block["tunes"] ?? []
            );
            if (!empty($sanitizedBlock)) {
                array_push($sanitizedBlocks, $sanitizedBlock);
            }
        }

        return $sanitizedBlocks;
    }

    private function validateBlocks()
    {
        foreach ($this->blocks as $block) {
            if (!$this->handler->validateBlock($block['type'], $block['data'])) {
                return false;
            }
        }

        return true;
    }

    public static function render(string $data): string
    {
        try {
            $configJson = json_encode(config('tena-editor.config') ?: []);

            $editor = new TenaEditor($data, $configJson);
            $renderedBlocks = [];

            foreach ($editor->getBlocks() as $block) {
                $viewName = sprintf("tena-editor::blocks.%s", Str::snake($block['type'], '-'));
                if(! View::exists($viewName)) {
                    $viewName = 'tena-editor::blocks.not-found';
                }
                $renderedBlocks[] = View::make($viewName, [
                    'type' => $block['type'],
                    'data' => $block['data']
                ]);
            }
            return implode($renderedBlocks);
        }catch (TenaEditorException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
