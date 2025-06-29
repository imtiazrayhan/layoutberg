# Debugging LayoutBerg 500 Error

## Quick Debug Steps

1. **Enable WordPress Debug Mode**
   
   Add to your `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Run the Debug Test**
   
   Visit: `http://localhost:10089/wp-content/plugins/layoutberg/debug-test.php`
   
   This will show you:
   - Which classes are loaded correctly
   - API key status
   - Component initialization status
   - Any immediate errors

3. **Check the Error Log**
   
   After trying to generate a layout, check:
   - WordPress debug log: `/wp-content/debug.log`
   - Browser console (F12) for JavaScript errors
   - Network tab for the actual API response

4. **Common Issues and Solutions**

   **Issue: "OpenAI API key is not configured"**
   - Go to LayoutBerg → Settings
   - Enter your API key
   - Click "Test Connection" to verify
   
   **Issue: "Class not found" errors**
   - Run: `composer dump-autoload` in the plugin directory
   
   **Issue: "Quota exceeded"**
   - This is an OpenAI account issue
   - Add payment method at https://platform.openai.com/account/billing/payment-methods
   
   **Issue: JavaScript errors**
   - Make sure you've run `npm run build` to compile the JavaScript

5. **Test API Directly**
   
   You can test if the REST API is working:
   ```bash
   curl -X POST http://localhost:10089/wp-json/layoutberg/v1/generate \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE_HERE" \
     -d '{
       "prompt": "Create a simple hero section",
       "settings": {
         "model": "gpt-3.5-turbo",
         "maxTokens": 500
       }
     }'
   ```

## What to Look For

1. In the debug test output, all classes should show ✓ EXISTS
2. API Key should show ✓ Configured and ✓ Valid
3. No red error messages or exceptions

## Getting the Actual Error

Since you're getting a 500 error:

1. Check the browser's Network tab:
   - Look for the failed request to `/wp-json/layoutberg/v1/generate`
   - Click on it and check the "Response" tab
   - This often shows the actual PHP error

2. Check WordPress debug.log:
   - Look for entries starting with "LayoutBerg"
   - Look for PHP Fatal errors or Exceptions

3. The debug test page will help identify if it's:
   - Missing API key
   - Class loading issue
   - API connection problem
   - Something else

## Still Having Issues?

If the debug test shows everything is OK but you still get 500 errors:

1. The issue is likely the OpenAI API quota (most common)
2. Or a timeout issue (if generation takes too long)
3. Or a memory limit issue (increase PHP memory limit)

Share the output from the debug test and any error messages from the logs, and we can pinpoint the exact issue.