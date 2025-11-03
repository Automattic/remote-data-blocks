# Verification Guide: Pattern Selection Fix

This guide provides detailed instructions for verifying the pattern selection bug fix in a local development environment.

## Overview

**Issue**: Pattern selection modal was always inserting the first pattern regardless of which pattern was selected.

**Fix**: Changed pattern lookup from comparing by optional `id` property to comparing by required `name` property.

## Prerequisites

- Docker and Docker Compose installed
- Node.js and npm installed (see `.nvmrc` for version)
- PHP 8.1+ and Composer installed

## Setup Local Development Environment

1. **Clone and install dependencies**:
   ```bash
   git clone https://github.com/Automattic/remote-data-blocks.git
   cd remote-data-blocks
   git checkout copilot/fix-pattern-selection-bug
   npm install
   ```

2. **Start the development environment**:
   ```bash
   npm run dev
   ```
   
   This will:
   - Start WordPress at `http://localhost:8888`
   - Build and watch for code changes
   - Enable Xdebug for debugging
   - Start a Valkey (Redis) instance for object cache
   
   Login credentials: `admin` / `password`

## Create a Test Block with Multiple Patterns

To verify the fix, you need a block with multiple patterns. Here's how to create one:

1. **Create a test file** at `example/blocks/pattern-test-block/pattern-test-block.php`:

```php
<?php
namespace RemoteDataBlocks\Examples\PatternTestBlock;

function register_pattern_test_block() {
    register_remote_data_block( [
        'title' => 'Pattern Test Block',
        'icon' => 'list-view',
        'render_query' => [
            'query' => [
                'endpoint' => 'https://jsonplaceholder.typicode.com/posts/1',
                'method' => 'GET',
                'output_schema' => [
                    'type' => [
                        'title' => [ 'name' => 'Title', 'type' => 'string' ],
                        'body' => [ 'name' => 'Body', 'type' => 'string' ],
                    ],
                ],
            ],
        ],
        'patterns' => [
            [
                'title' => 'Pattern 1 - Simple',
                'html' => '<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test-block","field":"title"}}}}} -->
<h2 class="wp-block-heading"></h2>
<!-- /wp:heading -->',
            ],
            [
                'title' => 'Pattern 2 - With Background',
                'html' => '<!-- wp:group {"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-cyan-blue-background-color has-background">
<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test-block","field":"title"}}}}} -->
<h2 class="wp-block-heading"></h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->',
            ],
            [
                'title' => 'Pattern 3 - With Body',
                'html' => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test-block","field":"title"}}}}} -->
<h2 class="wp-block-heading"></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test-block","field":"body"}}}}} -->
<p></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
            ],
        ],
    ] );
}
add_action( 'init', __NAMESPACE__ . '\\register_pattern_test_block' );
```

2. **Activate the test block** by adding this line to `functions.php`:

```php
require_once __DIR__ . '/example/blocks/pattern-test-block/pattern-test-block.php';
```

3. **Refresh the WordPress environment**:
   ```bash
   npm run dev:stop
   npm run dev
   ```

## Testing Steps

### Before the Fix (to reproduce the bug)

1. Checkout the commit before the fix:
   ```bash
   git checkout 76154d0  # Commit before the fix
   npm run dev:stop
   npm run dev
   ```

2. Navigate to `http://localhost:8888/wp-admin/post-new.php`

3. Click the `+` button to add a block

4. Search for "Pattern Test Block" and insert it

5. Click "Choose a pattern"

6. **Select the second pattern** ("Pattern 2 - With Background")

7. **Bug**: Notice that "Pattern 1 - Simple" is inserted instead (no background color)

8. Try selecting the third pattern - it still inserts the first pattern

### After the Fix (to verify the fix works)

1. Checkout the fix branch:
   ```bash
   git checkout copilot/fix-pattern-selection-bug
   npm run dev:stop
   npm run dev
   ```

2. Navigate to `http://localhost:8888/wp-admin/post-new.php`

3. Click the `+` button to add a block

4. Search for "Pattern Test Block" and insert it

5. Click "Choose a pattern"

6. **Select the second pattern** ("Pattern 2 - With Background")

7. **Expected**: The block should now have a light blue background, confirming Pattern 2 was inserted

8. Delete the block and repeat:
   - Insert "Pattern Test Block" again
   - Select "Pattern 3 - With Body"
   - **Expected**: You should see both a heading and a paragraph (body text)

### Visual Verification

You should see these differences:

- **Pattern 1 (Simple)**: Just a heading
- **Pattern 2 (With Background)**: Heading with light blue background
- **Pattern 3 (With Body)**: Heading and paragraph text

### Console Verification (Optional)

1. Open browser DevTools (F12)
2. Go to the Console tab
3. When selecting a pattern, you should see the `realPattern` object matching the selected pattern's name
4. Before the fix: `realPattern` would be `undefined` and fall back to first pattern
5. After the fix: `realPattern` should match the selected pattern

## Alternative Testing with Existing Example Blocks

If you prefer to test with existing blocks:

1. **Use the GitHub Markdown Block**:
   ```bash
   # Ensure example blocks are loaded in functions.php
   ```

2. Navigate to Settings > Remote Data Blocks and verify the GitHub integration is configured

3. Insert a "GitHub Markdown" block and test pattern selection if multiple patterns are available

## Cleanup

When you're done testing:

```bash
npm run dev:stop
# Or to completely destroy the environment:
npm run dev:destroy
```

## Expected Results

✅ **Pass**: Selected pattern is correctly inserted into the editor
✅ **Pass**: Different patterns produce visually different outputs
✅ **Pass**: Pattern selection modal shows all available patterns
✅ **Pass**: Console logs show `realPattern` matching the selected pattern

❌ **Fail**: First pattern is always inserted regardless of selection
❌ **Fail**: `realPattern` is `undefined` in console logs

## Troubleshooting

### Build not reflecting changes
```bash
npm run dev:stop
npm install
npm run dev
```

### WordPress not loading
```bash
npx wp-env logs
```

### Test block not appearing
Check that the `require_once` line was added to `functions.php` and the environment was restarted.

## Additional Notes

- The fix is in `src/blocks/remote-data-container/hooks/usePatterns.ts` line 107
- Changed from `p.id === pattern.id` to `p.name === pattern.name`
- The `name` property is the required unique identifier for patterns (e.g., `'remote-data-blocks/community-card'`)
- The `id` property is optional and often `undefined` for custom patterns
