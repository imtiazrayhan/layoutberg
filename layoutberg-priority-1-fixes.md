# LayoutBerg Priority 1 Fixes - Implementation Guide

## Overview
This document provides detailed implementation guidance for the four critical Priority 1 fixes identified in the LayoutBerg layout generation review. These fixes address fundamental issues that impact reliability, performance, and user experience.

## 1. Fix Double Block Parsing Issue

### Problem Description
Currently, the system parses WordPress blocks twice - once in PHP and once in JavaScript. This causes:
- Performance overhead
- Potential parsing inconsistencies
- Validation errors on valid blocks
- Unnecessary complexity

### Current Implementation
```php
// In includes/class-block-generator.php (line ~92-105)
$blocks = parse_blocks($content); // PHP parsing
$validated = $this->validate_blocks($blocks);
if (is_wp_error($validated)) {
    return $validated;
}
// ... serialization and return
```

```javascript
// In src/editor/index.js
const parsedBlocks = wp.blocks.parse(response.data.blocks); // JS parsing again
```

### Proposed Solution

#### Step 1: Modify PHP Block Generator
```php
// includes/class-block-generator.php
public function generate($prompt, $options = array()) {
    // ... existing API call code ...
    
    $content = $result['content'];
    
    // Remove markdown code blocks if present
    $content = preg_replace('/^```html\s*/', '', $content);
    $content = preg_replace('/```$/', '', $content);
    $content = trim($content);
    
    // Basic validation - just check if it looks like blocks
    if (!preg_match('/<!-- wp:/', $content)) {
        return new \WP_Error(
            'invalid_block_markup',
            __('Generated content does not contain valid block markup.', 'layoutberg')
        );
    }
    
    // DO NOT parse blocks here - return raw content
    $response = array(
        'blocks'     => $content,  // Raw block content
        'serialized' => $content,  // Same content for compatibility
        'html'       => '',        // Don't generate HTML here
        'raw'        => $result['content'],
        'usage'      => $result['usage'],
        'model'      => $result['model'],
        'prompts'    => isset($result['prompts']) ? $result['prompts'] : null,
        'metadata'   => array(
            'prompt'     => $prompt,
            'options'    => $options,
            'generated'  => current_time('mysql'),
        ),
    );
    
    return $response;
}
```

#### Step 2: Update JavaScript Handler
```javascript
// src/editor/index.js
const handleGenerate = async () => {
    // ... existing validation ...
    
    try {
        const response = await apiFetch({
            path: '/layoutberg/v1/generate',
            method: 'POST',
            data: {
                prompt: prompt.trim(),
                settings: settings,
                replace_selected: hasSelectedBlocks
            }
        });
        
        if (response.success && response.data && response.data.blocks) {
            // Parse blocks ONCE in JavaScript
            const parsedBlocks = wp.blocks.parse(response.data.blocks);
            
            // Validate parsed blocks
            if (!parsedBlocks || parsedBlocks.length === 0) {
                throw new Error(__('No valid blocks found in generated content.', 'layoutberg'));
            }
            
            // Filter out empty blocks
            const validBlocks = parsedBlocks.filter(block => 
                block.blockName !== null || 
                (block.innerBlocks && block.innerBlocks.length > 0)
            );
            
            if (validBlocks.length === 0) {
                throw new Error(__('Generated content contained only empty blocks.', 'layoutberg'));
            }
            
            // Insert blocks using WordPress dispatch
            const { removeBlocks, insertBlocks } = wp.data.dispatch('core/block-editor');
            
            if (hasSelectedBlocks) {
                removeBlocks(selectedBlocks);
            }
            
            insertBlocks(validBlocks);
            
            // ... success handling ...
        }
    } catch (error) {
        // ... error handling ...
    }
};
```

## 2. Implement Proper State Management

### Problem Description
Multiple state updates can cause race conditions and inconsistent UI states during generation.

### Current Implementation
```javascript
// Multiple separate state calls
setIsGenerating(true);
setGenerationError(null);
setGenerationState('preparing');
```

### Proposed Solution

#### Step 1: Create State Reducer
```javascript
// src/editor/state/generationReducer.js
export const GENERATION_ACTIONS = {
    START: 'START_GENERATION',
    UPDATE_STATE: 'UPDATE_GENERATION_STATE',
    SUCCESS: 'GENERATION_SUCCESS',
    ERROR: 'GENERATION_ERROR',
    RESET: 'RESET_GENERATION'
};

export const initialGenerationState = {
    isGenerating: false,
    state: 'idle', // idle, preparing, sending, generating, processing, complete
    error: null,
    lastResponse: null,
    lastGeneratedBlocks: null
};

export function generationReducer(state, action) {
    switch (action.type) {
        case GENERATION_ACTIONS.START:
            return {
                ...state,
                isGenerating: true,
                state: 'preparing',
                error: null
            };
            
        case GENERATION_ACTIONS.UPDATE_STATE:
            return {
                ...state,
                state: action.payload
            };
            
        case GENERATION_ACTIONS.SUCCESS:
            return {
                ...state,
                isGenerating: false,
                state: 'complete',
                error: null,
                lastResponse: action.payload.response,
                lastGeneratedBlocks: action.payload.blocks
            };
            
        case GENERATION_ACTIONS.ERROR:
            return {
                ...state,
                isGenerating: false,
                state: 'idle',
                error: action.payload
            };
            
        case GENERATION_ACTIONS.RESET:
            return initialGenerationState;
            
        default:
            return state;
    }
}
```

#### Step 2: Update Component to Use Reducer
```javascript
// src/editor/index.js
import { useReducer } from '@wordpress/element';
import { generationReducer, initialGenerationState, GENERATION_ACTIONS } from './state/generationReducer';

export function LayoutBergEditor() {
    const [generationState, dispatch] = useReducer(generationReducer, initialGenerationState);
    
    const handleGenerate = async () => {
        if (!prompt.trim()) {
            dispatch({ 
                type: GENERATION_ACTIONS.ERROR, 
                payload: __('Please enter a prompt.', 'layoutberg') 
            });
            return;
        }
        
        dispatch({ type: GENERATION_ACTIONS.START });
        
        try {
            // Small delay for UI feedback
            await new Promise(resolve => setTimeout(resolve, 300));
            
            dispatch({ type: GENERATION_ACTIONS.UPDATE_STATE, payload: 'sending' });
            
            const response = await apiFetch({
                path: '/layoutberg/v1/generate',
                method: 'POST',
                data: { /* ... */ }
            });
            
            dispatch({ type: GENERATION_ACTIONS.UPDATE_STATE, payload: 'processing' });
            
            // ... process response ...
            
            dispatch({ 
                type: GENERATION_ACTIONS.SUCCESS, 
                payload: { 
                    response: response.data, 
                    blocks: parsedBlocks 
                } 
            });
            
        } catch (error) {
            dispatch({ 
                type: GENERATION_ACTIONS.ERROR, 
                payload: error.message || __('Generation failed.', 'layoutberg') 
            });
        }
    };
    
    // ... rest of component
}
```

## 3. Add Database Indexes for Performance

### Problem Description
Missing indexes on frequently queried columns cause slow queries, especially as data grows.

### Current Schema Issues
- No indexes on `user_id` columns
- No indexes on `status` columns
- No composite indexes for common query patterns

### Proposed Solution

#### Step 1: Create Database Migration
```php
// includes/class-database-upgrader.php
<?php
namespace DotCamp\LayoutBerg;

class Database_Upgrader {
    private static $db_version = '1.1.0';
    
    public static function upgrade() {
        global $wpdb;
        
        $current_version = get_option('layoutberg_db_version', '1.0.0');
        
        if (version_compare($current_version, '1.1.0', '<')) {
            self::upgrade_to_1_1_0();
        }
        
        update_option('layoutberg_db_version', self::$db_version);
    }
    
    private static function upgrade_to_1_1_0() {
        global $wpdb;
        
        // Add indexes to generations table
        $table_generations = $wpdb->prefix . 'layoutberg_generations';
        $wpdb->query("ALTER TABLE {$table_generations} 
            ADD INDEX idx_user_status (user_id, status),
            ADD INDEX idx_created_at (created_at),
            ADD INDEX idx_status (status)");
        
        // Add indexes to usage table
        $table_usage = $wpdb->prefix . 'layoutberg_usage';
        $wpdb->query("ALTER TABLE {$table_usage} 
            ADD INDEX idx_user_date (user_id, date),
            ADD INDEX idx_model (model)");
        
        // Add indexes to templates table
        $table_templates = $wpdb->prefix . 'layoutberg_templates';
        $wpdb->query("ALTER TABLE {$table_templates} 
            ADD INDEX idx_user_id (user_id),
            ADD INDEX idx_category (category),
            ADD INDEX idx_created_at (created_at),
            ADD INDEX idx_user_category (user_id, category)");
    }
}
```

#### Step 2: Update Activation Hook
```php
// includes/class-activator.php
public static function activate() {
    // ... existing activation code ...
    
    // Run database upgrade
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-database-upgrader.php';
    Database_Upgrader::upgrade();
}
```

#### Step 3: Add Upgrade Routine
```php
// layoutberg.php
add_action('plugins_loaded', function() {
    $db_version = get_option('layoutberg_db_version', '1.0.0');
    if (version_compare($db_version, LAYOUTBERG_DB_VERSION, '<')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-database-upgrader.php';
        \DotCamp\LayoutBerg\Database_Upgrader::upgrade();
    }
});
```

## 4. Update Model Configurations

### Problem Description
Model configurations are incomplete and don't reflect actual API limits, causing:
- Token limit errors
- Incorrect cost calculations
- Poor model selection guidance

### Proposed Solution

#### Step 1: Create Comprehensive Model Configuration
```php
// includes/class-model-config.php
<?php
namespace DotCamp\LayoutBerg;

class Model_Config {
    /**
     * Model configurations with accurate limits
     */
    const MODELS = [
        // OpenAI Models
        'gpt-3.5-turbo' => [
            'provider' => 'openai',
            'name' => 'GPT-3.5 Turbo',
            'description' => 'Fast and affordable',
            'context_window' => 16385,
            'max_output' => 4096,
            'cost_per_1k_input' => 0.0005,
            'cost_per_1k_output' => 0.0015,
            'supports_json_mode' => true,
            'supports_functions' => true
        ],
        'gpt-4' => [
            'provider' => 'openai',
            'name' => 'GPT-4',
            'description' => 'Most capable for complex tasks',
            'context_window' => 8192,
            'max_output' => 4096,
            'cost_per_1k_input' => 0.03,
            'cost_per_1k_output' => 0.06,
            'supports_json_mode' => true,
            'supports_functions' => true
        ],
        'gpt-4-turbo' => [
            'provider' => 'openai',
            'name' => 'GPT-4 Turbo',
            'description' => 'Latest model with 128k context',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03,
            'supports_json_mode' => true,
            'supports_functions' => true
        ],
        'gpt-4o' => [
            'provider' => 'openai',
            'name' => 'GPT-4 Optimized',
            'description' => 'Faster GPT-4 variant',
            'context_window' => 128000,
            'max_output' => 4096,
            'cost_per_1k_input' => 0.005,
            'cost_per_1k_output' => 0.015,
            'supports_json_mode' => true,
            'supports_functions' => true
        ],
        
        // Claude Models
        'claude-3-opus-20240229' => [
            'provider' => 'claude',
            'name' => 'Claude 3 Opus',
            'description' => 'Most powerful Claude model',
            'context_window' => 200000,
            'max_output' => 4096,
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.075,
            'supports_json_mode' => false,
            'supports_functions' => false
        ],
        'claude-3-5-sonnet-20241022' => [
            'provider' => 'claude',
            'name' => 'Claude 3.5 Sonnet',
            'description' => 'Latest balanced Claude model',
            'context_window' => 200000,
            'max_output' => 8192,
            'cost_per_1k_input' => 0.003,
            'cost_per_1k_output' => 0.015,
            'supports_json_mode' => false,
            'supports_functions' => false
        ],
        'claude-3-haiku-20240307' => [
            'provider' => 'claude',
            'name' => 'Claude 3 Haiku',
            'description' => 'Fast and affordable Claude model',
            'context_window' => 200000,
            'max_output' => 4096,
            'cost_per_1k_input' => 0.00025,
            'cost_per_1k_output' => 0.00125,
            'supports_json_mode' => false,
            'supports_functions' => false
        ]
    ];
    
    /**
     * Get model configuration
     */
    public static function get_model($model_id) {
        return self::MODELS[$model_id] ?? null;
    }
    
    /**
     * Get all models for a provider
     */
    public static function get_provider_models($provider) {
        return array_filter(self::MODELS, function($model) use ($provider) {
            return $model['provider'] === $provider;
        });
    }
    
    /**
     * Calculate safe max tokens for generation
     */
    public static function calculate_max_tokens($model_id, $prompt_tokens, $buffer = 500) {
        $config = self::get_model($model_id);
        if (!$config) {
            return 2000; // Safe default
        }
        
        $available = $config['context_window'] - $prompt_tokens - $buffer;
        return min($available, $config['max_output']);
    }
    
    /**
     * Estimate cost for a generation
     */
    public static function estimate_cost($model_id, $input_tokens, $output_tokens) {
        $config = self::get_model($model_id);
        if (!$config) {
            return 0;
        }
        
        $input_cost = ($input_tokens / 1000) * $config['cost_per_1k_input'];
        $output_cost = ($output_tokens / 1000) * $config['cost_per_1k_output'];
        
        return $input_cost + $output_cost;
    }
}
```

#### Step 2: Update API Client to Use Model Config
```php
// includes/class-api-client.php
private function prepare_request($prompt, $options) {
    $model_config = Model_Config::get_model($this->model);
    
    if (!$model_config) {
        return new \WP_Error(
            'invalid_model',
            sprintf(__('Invalid model selected: %s', 'layoutberg'), $this->model)
        );
    }
    
    // Calculate token limits
    $prompt_tokens = $this->estimate_tokens($system_prompt . $user_prompt);
    $max_tokens = Model_Config::calculate_max_tokens(
        $this->model, 
        $prompt_tokens,
        500 // Buffer
    );
    
    if ($max_tokens < 500) {
        return new \WP_Error(
            'prompt_too_long',
            __('Your prompt is too long for the selected model. Try a shorter description or use a model with a larger context window.', 'layoutberg')
        );
    }
    
    // Update max_tokens
    $this->max_tokens = min($max_tokens, $options['max_tokens'] ?? 4096);
    
    // ... rest of request preparation
}
```

#### Step 3: Update Settings UI
```javascript
// src/admin/components/ModelSelector.js
import { Model_Config } from '../config/models';

export function ModelSelector({ selectedModel, onChange, availableProviders }) {
    const models = Model_Config.getAvailableModels(availableProviders);
    
    return (
        <div className="layoutberg-model-selector">
            <select 
                value={selectedModel} 
                onChange={(e) => onChange(e.target.value)}
                className="layoutberg-select"
            >
                {Object.entries(models).map(([provider, providerModels]) => (
                    <optgroup key={provider} label={provider}>
                        {providerModels.map(model => (
                            <option key={model.id} value={model.id}>
                                {model.name} - {model.description}
                                {model.context_window > 100000 && ' (Long context)'}
                            </option>
                        ))}
                    </optgroup>
                ))}
            </select>
            
            {selectedModel && (
                <div className="layoutberg-model-info">
                    <p>Context: {models[selectedModel].context_window.toLocaleString()} tokens</p>
                    <p>Max output: {models[selectedModel].max_output.toLocaleString()} tokens</p>
                    <p>Cost: ${models[selectedModel].cost_per_1k_input}/1k input, 
                       ${models[selectedModel].cost_per_1k_output}/1k output</p>
                </div>
            )}
        </div>
    );
}
```

## Testing Strategy

### 1. Double Parsing Fix Tests
```javascript
// tests/e2e/generation-flow.test.js
describe('Generation Flow', () => {
    it('should parse blocks only once in JavaScript', async () => {
        const spy = jest.spyOn(wp.blocks, 'parse');
        
        await generateLayout('Create a hero section');
        
        expect(spy).toHaveBeenCalledTimes(1);
    });
    
    it('should handle malformed block content gracefully', async () => {
        // Test with various edge cases
    });
});
```

### 2. State Management Tests
```javascript
// tests/unit/generationReducer.test.js
describe('Generation Reducer', () => {
    it('should handle state transitions correctly', () => {
        const state = generationReducer(initialState, { type: GENERATION_ACTIONS.START });
        expect(state.isGenerating).toBe(true);
        expect(state.state).toBe('preparing');
    });
});
```

### 3. Database Performance Tests
```sql
-- Test query performance after indexes
EXPLAIN SELECT * FROM wp_layoutberg_generations 
WHERE user_id = 1 AND status = 'completed' 
ORDER BY created_at DESC LIMIT 10;
```

### 4. Model Configuration Tests
```php
// tests/phpunit/test-model-config.php
public function test_calculate_max_tokens() {
    $max_tokens = Model_Config::calculate_max_tokens('gpt-4-turbo', 1000);
    $this->assertLessThanOrEqual(4096, $max_tokens);
    $this->assertGreaterThan(0, $max_tokens);
}
```

## Deployment Checklist

- [ ] Backup database before applying migrations
- [ ] Test database upgrades on staging environment
- [ ] Verify all model configurations with actual API calls
- [ ] Run full test suite
- [ ] Update documentation with new model information
- [ ] Monitor error logs after deployment
- [ ] Check performance metrics post-index creation
- [ ] Verify state management doesn't break existing functionality

## Rollback Plan

1. **Database**: Keep backup of pre-migration state
2. **Code**: Tag release for easy reversion
3. **Config**: Export current settings before update
4. **Monitor**: Watch error rates for 24 hours post-deploy