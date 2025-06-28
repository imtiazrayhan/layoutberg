# LayoutBerg Troubleshooting Guide

## Common Issues

### 1. API Key Test Shows Invalid After Saving

**Problem**: The API key test works when first entered but shows as invalid after saving settings.

**Solution**: This issue has been fixed. The plugin now properly:
- Preserves encrypted API keys when saving settings
- Validates stored API keys correctly
- Shows proper error messages for different failure reasons

**To verify your API key**:
1. Go to LayoutBerg → Settings
2. Click "Test Connection"
3. Check the status message

### 2. OpenAI Quota Exceeded Error

**Problem**: Getting "You exceeded your current quota, please check your plan and billing details" error.

**Solution**: This is an OpenAI account issue, not a plugin issue. To fix:

1. **Check your OpenAI account**:
   - Visit https://platform.openai.com/account/usage
   - Check your current usage and limits
   - Verify your billing status

2. **Common causes**:
   - **Free trial expired**: OpenAI provides $5 in free credits that expire after 3 months
   - **No payment method**: You need to add a payment method after free credits expire
   - **Usage limit reached**: Check if you've hit your monthly spending limit

3. **How to fix**:
   - Add a payment method at https://platform.openai.com/account/billing/payment-methods
   - Set up a monthly budget at https://platform.openai.com/account/limits
   - Wait for the next billing cycle if you've hit your limit

4. **Verify your API key is working**:
   ```bash
   curl https://api.openai.com/v1/models \
     -H "Authorization: Bearer YOUR_API_KEY_HERE"
   ```

### 3. 500 Internal Server Error

**Problem**: Getting server errors when trying to generate layouts.

**Possible causes and solutions**:

1. **Invalid API key format**: Ensure your API key starts with "sk-" and is 51 characters long
2. **PHP errors**: Check your WordPress debug log at `wp-content/debug.log`
3. **Memory limits**: Increase PHP memory limit in wp-config.php:
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

### 4. Generated Layouts Not Appearing

**Problem**: Layout generation succeeds but content doesn't appear in editor.

**Solutions**:
1. Check browser console for JavaScript errors
2. Ensure you have the latest version of WordPress and Gutenberg
3. Try disabling other plugins that might conflict with the block editor

## Debugging Tips

### Enable Debug Mode

Add to your `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Error Logs

1. **WordPress debug log**: `/wp-content/debug.log`
2. **PHP error log**: Location varies by hosting provider
3. **Browser console**: Press F12 and check Console tab

### Test API Connection

You can test your OpenAI API connection directly:

1. **Via LayoutBerg settings**:
   - Go to LayoutBerg → Settings
   - Click "Test Connection" button

2. **Via command line**:
   ```bash
   curl https://api.openai.com/v1/chat/completions \
     -H "Authorization: Bearer YOUR_API_KEY_HERE" \
     -H "Content-Type: application/json" \
     -d '{
       "model": "gpt-3.5-turbo",
       "messages": [{"role": "user", "content": "Say hello"}],
       "max_tokens": 10
     }'
   ```

### Common Error Messages

| Error | Meaning | Solution |
|-------|---------|----------|
| "Invalid API key" | API key is incorrect or revoked | Generate a new API key from OpenAI |
| "Quota exceeded" | Account limit reached | Add payment method or wait for reset |
| "Rate limit exceeded" | Too many requests | Wait a few minutes and try again |
| "Model not found" | Using wrong model name | Use gpt-3.5-turbo or gpt-4 |

## Getting Help

If you're still experiencing issues:

1. **Check the documentation**: https://layoutberg.com/docs
2. **Search existing issues**: https://github.com/layoutberg/issues
3. **Create a new issue** with:
   - WordPress version
   - PHP version
   - Error messages (check debug.log)
   - Steps to reproduce

## Increasing Token Limits

The plugin now supports up to 8000 tokens per generation. To use higher limits:

1. Go to LayoutBerg → Settings → Generation tab
2. Set "Max Tokens" up to 8000
3. Note: Higher token usage = higher API costs

**Token costs** (approximate):
- GPT-3.5-turbo: $0.002 per 1K tokens
- GPT-4: $0.03 per 1K tokens
- GPT-4-turbo: $0.01 per 1K tokens

A typical layout generation uses 500-2000 tokens depending on complexity.