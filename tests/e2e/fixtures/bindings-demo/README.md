# Bindings Garden fixture

This test-only plugin supplies a real Remote Data Block (`remote-data-blocks/bindings-garden`) whose query runner returns local values through the normal query parser. It never calls its `example.invalid` endpoint. Its image is a local SVG. Do not install this fixture in production.

`.wp-env.json` mounts it only in the tests environment. After building the plugin:

```sh
npx wp-env start --update
npx wp-env run tests-cli wp plugin activate bindings-demo
npm run test:e2e -- tests/e2e/bindings
```

The default test URL is `http://localhost:8889`. Set `WP_BASE_URL` when using other wp-env ports. The custom-block test skips only when the actual runtime is WordPress 6.7/6.8 and has no supported-attributes map. The core author/save/reload/frontend test must still pass. CI sets `RDB_E2E_LEGACY=1` for the minimum lane to additionally assert it really runs WordPress 6.7. Setting that flag on current stable fails; a missing modern capability map or either missing filter also fails.

## Manual walkthrough

1. Create a post and insert **Bindings Garden**. Its default template displays the local fields; no credentials or network service are needed.
2. Add a **Button** inside it. In **Remote Data**, its URL selector offers **Garden link** (`button_url`), not **Garden illustration** (`image_url`).
3. Add an **Image**, then bind its URL and alt text. Current WordPress also exposes title/caption controls; bind **Garden caption** to caption.
4. Add **Garden note (static)** and bind its previously unbound **Botanical Note** attribute to **Garden caption**. The generic WordPress filter supplies this attribute, which is absent from all production override maps.
5. Add **Garden stock (dynamic)** and bind **Plant Count** to **Remaining plants** and **Is Available** to **Available today**. The block-specific WordPress filter supplies both attributes. The block must show `0` and `false`, not its defaults (`12` and `true`).
6. Publish, reload the editor, then open the frontend. Verify the same values and bindings survive.

The Playwright helpers in `tests/e2e/bindings/helpers.ts` insert an unbound template using actual query results for repeatable authoring/capture. The tests select fields using the real inspector; they do not stub editor settings, filters, binding resolvers or network responses.
